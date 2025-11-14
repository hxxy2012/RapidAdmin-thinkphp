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
use think\facade\Cache;
use think\facade\Log;

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
        try {
            // 1. 识别租户
            $tenantCode = $this->resolveTenant($request);

            if (empty($tenantCode)) {
                return $this->errorResponse('未识别到租户信息', 401);
            }

            // 2. 获取租户信息（带缓存）
            $tenant = $this->getTenantInfo($tenantCode);

            if (!$tenant) {
                return $this->errorResponse('租户不存在', 404);
            }

            // 3. 检查租户状态
            if (!$tenant->isAvailable()) {
                $msg = $this->getTenantStatusMessage($tenant);
                return $this->errorResponse($msg, 403);
            }

            // 4. 设置租户上下文
            TenantContext::setTenantInfo($tenant->toArray());

            // 5. 切换数据库连接（如果使用独立数据库模式）
            if ($this->shouldSwitchDatabase()) {
                $this->switchDatabase($tenant);
            }

            // 6. 执行请求
            $response = $next($request);

            return $response;

        } catch (\Throwable $e) {
            // 记录错误日志
            Log::error('TenantMiddleware Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return $this->errorResponse('系统错误: ' . $e->getMessage(), 500);

        } finally {
            // 确保清理租户上下文
            // 注意：在end()方法中也会清理
        }
    }

    /**
     * 识别租户
     *
     * @param \think\Request $request
     * @return string|null
     */
    protected function resolveTenant($request)
    {
        // 获取租户识别优先级配置
        $priority = Config::get('tenant.resolve_priority', [
            'header', 'subdomain', 'token', 'session', 'param'
        ]);

        foreach ($priority as $method) {
            $tenantCode = null;

            switch ($method) {
                case 'header':
                    $tenantCode = $this->resolveFromHeader($request);
                    break;
                case 'subdomain':
                    $tenantCode = $this->resolveFromSubdomain($request);
                    break;
                case 'token':
                    $tenantCode = $this->resolveFromToken($request);
                    break;
                case 'session':
                    $tenantCode = $this->resolveFromSession($request);
                    break;
                case 'param':
                    // 仅开发环境
                    if (app()->isDebug()) {
                        $tenantCode = $this->resolveFromParam($request);
                    }
                    break;
            }

            if ($tenantCode) {
                return $tenantCode;
            }
        }

        return null;
    }

    /**
     * 从Header中识别租户
     *
     * @param \think\Request $request
     * @return string|null
     */
    protected function resolveFromHeader($request)
    {
        return $request->header('X-Tenant-Code') ?: $request->header('X-Tenant-Id');
    }

    /**
     * 从子域名识别租户
     *
     * @param \think\Request $request
     * @return string|null
     */
    protected function resolveFromSubdomain($request)
    {
        $host = $request->host();
        $parts = explode('.', $host);

        // 如果是子域名，第一部分就是租户编码
        // 例如: demo.yourdomain.com -> demo
        if (count($parts) >= 3) {
            // 过滤掉www
            if ($parts[0] !== 'www' && $parts[0] !== 'api') {
                return $parts[0];
            }
        }

        return null;
    }

    /**
     * 从Token中识别租户
     *
     * @param \think\Request $request
     * @return string|null
     */
    protected function resolveFromToken($request)
    {
        $token = $request->header('Authorization');
        if (!$token) {
            return null;
        }

        try {
            // 移除Bearer前缀
            $token = str_replace('Bearer ', '', $token);

            // TODO: 实现JWT解析逻辑
            // 这里需要根据实际的JWT实现来解析
            // $payload = JWT::decode($token);
            // return $payload->tenant_code ?? null;

            return null;
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * 从Session中识别租户
     *
     * @param \think\Request $request
     * @return string|null
     */
    protected function resolveFromSession($request)
    {
        return session('tenant_code');
    }

    /**
     * 从URL参数识别租户（仅开发环境）
     *
     * @param \think\Request $request
     * @return string|null
     */
    protected function resolveFromParam($request)
    {
        return $request->param('tenant_code') ?: $request->param('tenant');
    }

    /**
     * 获取租户信息（带缓存）
     *
     * @param string $tenantCode
     * @return Tenant|null
     */
    protected function getTenantInfo($tenantCode)
    {
        // 检查是否启用缓存
        $cacheEnabled = Config::get('tenant.cache.enabled', true);

        if (!$cacheEnabled) {
            return Tenant::getByCode($tenantCode);
        }

        // 使用缓存
        $cacheKey = Config::get('tenant.cache.prefix', 'tenant:') . $tenantCode;
        $cacheExpire = Config::get('tenant.cache.expire', 3600);

        return Cache::remember($cacheKey, function () use ($tenantCode) {
            return Tenant::getByCode($tenantCode);
        }, $cacheExpire);
    }

    /**
     * 获取租户状态消息
     *
     * @param Tenant $tenant
     * @return string
     */
    protected function getTenantStatusMessage($tenant)
    {
        if ($tenant->status == Tenant::STATUS_DISABLED) {
            return '租户已被停用';
        }

        if ($tenant->status == Tenant::STATUS_EXPIRED || $tenant->is_expired) {
            return '租户已过期，请联系管理员续费';
        }

        return '租户不可用';
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
     * @throws \Exception
     */
    protected function switchDatabase(Tenant $tenant)
    {
        $dbName = $tenant->db_name;

        if (empty($dbName)) {
            // 如果没有配置数据库名，使用租户编码生成
            $dbPrefix = Config::get('tenant.database_prefix', 'tenant_');
            $dbName = $dbPrefix . $tenant->tenant_code;
        }

        try {
            // 获取默认数据库配置
            $config = Config::get('database.connections.mysql');

            // 创建租户专属数据库配置
            $tenantConfig = array_merge($config, [
                'database' => $dbName,
                'prefix' => Config::get('database.prefix', 'ea_'),
            ]);

            // 动态添加租户数据库连接配置
            Config::set([
                'connections' => [
                    'tenant' => $tenantConfig
                ]
            ], 'database');

            // 测试连接是否可用
            $connection = Db::connect('tenant');
            $connection->query('SELECT 1');

            // 设置默认连接为租户连接
            // 注意：这会影响后续所有数据库操作
            // Db::setDefaultConnection('tenant');  // TP8中可能不需要这样设置

        } catch (\Exception $e) {
            // 数据库连接失败
            Log::error('Tenant database connection failed: ' . $e->getMessage(), [
                'tenant_code' => $tenant->tenant_code,
                'database' => $dbName,
            ]);

            throw new \Exception('租户数据库连接失败，请联系管理员');
        }
    }

    /**
     * 返回错误响应
     *
     * @param string $message
     * @param int $code
     * @return \think\Response
     */
    protected function errorResponse($message, $code = 400)
    {
        return json([
            'code' => $code,
            'msg' => $message,
            'data' => null
        ], $code);
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
