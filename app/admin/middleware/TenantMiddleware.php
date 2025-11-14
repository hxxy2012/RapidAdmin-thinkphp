<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 租户中间件 - 核心组件
// +----------------------------------------------------------------------

namespace app\admin\middleware;

use app\admin\model\Tenant;
use tenant\TenantContext;
use think\facade\Config;
use think\facade\Db;

/**
 * 租户中间件
 * 负责识别租户并切换数据库连接
 */
class TenantMiddleware
{
    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure       $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        // 1. 识别租户
        $tenantCode = $this->resolveTenant($request);

        if (empty($tenantCode)) {
            return json([
                'code' => 401,
                'msg' => '未识别到租户信息',
                'data' => null
            ], 401);
        }

        // 2. 获取租户信息
        $tenant = Tenant::getByCode($tenantCode);

        if (!$tenant) {
            return json([
                'code' => 404,
                'msg' => '租户不存在',
                'data' => null
            ], 404);
        }

        // 3. 检查租户状态
        if (!$tenant->isAvailable()) {
            $msg = '租户不可用';
            if ($tenant->status == Tenant::STATUS_DISABLED) {
                $msg = '租户已被停用';
            } elseif ($tenant->status == Tenant::STATUS_EXPIRED || $tenant->is_expired) {
                $msg = '租户已过期，请续费';
            }

            return json([
                'code' => 403,
                'msg' => $msg,
                'data' => null
            ], 403);
        }

        // 4. 设置租户上下文
        TenantContext::setTenantInfo($tenant->toArray());

        // 5. 切换数据库连接（如果使用独立数据库模式）
        if ($this->shouldSwitchDatabase()) {
            $this->switchDatabase($tenant);
        }

        // 6. 执行请求
        $response = $next($request);

        // 7. 清理租户上下文
        TenantContext::clear();

        return $response;
    }

    /**
     * 识别租户
     *
     * @param \think\Request $request
     * @return string|null
     */
    protected function resolveTenant($request)
    {
        // 方式1: 从Header中识别
        $tenantCode = $request->header('X-Tenant-Code');
        if ($tenantCode) {
            return $tenantCode;
        }

        // 方式2: 从子域名识别
        $host = $request->host();
        $parts = explode('.', $host);

        // 如果是子域名，第一部分就是租户编码
        // 例如: demo.yourdomain.com -> demo
        if (count($parts) >= 3) {
            // 过滤掉www
            if ($parts[0] !== 'www') {
                return $parts[0];
            }
        }

        // 方式3: 从URL参数识别（仅开发环境）
        if (app()->isDebug()) {
            $tenantCode = $request->param('tenant_code');
            if ($tenantCode) {
                return $tenantCode;
            }
        }

        // 方式4: 从JWT Token中识别（需要先解析token）
        $token = $request->header('Authorization');
        if ($token) {
            $tenantCode = $this->extractTenantFromToken($token);
            if ($tenantCode) {
                return $tenantCode;
            }
        }

        // 方式5: 从Session中获取（用户登录后）
        $tenantCode = session('tenant_code');
        if ($tenantCode) {
            return $tenantCode;
        }

        return null;
    }

    /**
     * 从Token中提取租户信息
     *
     * @param string $token
     * @return string|null
     */
    protected function extractTenantFromToken($token)
    {
        try {
            // 移除Bearer前缀
            $token = str_replace('Bearer ', '', $token);

            // TODO: 实现JWT解析逻辑
            // 这里需要根据实际的JWT实现来解析

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 判断是否需要切换数据库
     *
     * @return bool
     */
    protected function shouldSwitchDatabase()
    {
        // 从配置中读取是否启用独立数据库模式
        return Config::get('tenant.use_separate_database', true);
    }

    /**
     * 切换数据库连接
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function switchDatabase(Tenant $tenant)
    {
        $dbName = $tenant->db_name;

        if (empty($dbName)) {
            // 如果没有配置数据库名，使用租户编码生成
            $dbName = 'tenant_' . $tenant->tenant_code;
        }

        // 获取当前数据库配置
        $config = Config::get('database.connections.mysql');

        // 修改数据库名
        $config['database'] = $dbName;

        // 创建新的数据库连接配置
        Config::set([
            'connections' => [
                'tenant' => $config
            ]
        ], 'database');

        // 切换到租户数据库
        Db::connect('tenant');

        // 设置当前默认连接为租户连接
        Db::setConfig([
            'default' => 'tenant'
        ]);
    }

    /**
     * 中间件结束回调
     *
     * @param \think\Response $response
     * @return void
     */
    public function end(\think\Response $response)
    {
        // 清理租户上下文
        TenantContext::clear();
    }
}
