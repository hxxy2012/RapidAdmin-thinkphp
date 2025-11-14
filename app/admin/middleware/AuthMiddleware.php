<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 认证中间件
// +----------------------------------------------------------------------

namespace app\admin\middleware;

use extend\auth\JwtHelper;
use extend\tenant\TenantContext;
use think\facade\Cache;
use think\facade\Log;
use think\Response;

/**
 * 认证中间件
 *
 * 验证JWT Token并设置用户上下文
 */
class AuthMiddleware
{
    /**
     * 不需要认证的路由
     * @var array
     */
    protected $noAuthRequired = [
        '/admin/auth/login',
        '/admin/auth/captcha',
        '/admin/auth/refresh',
    ];

    /**
     * 处理请求
     *
     * @param \think\Request $request
     * @param \Closure $next
     * @return Response
     */
    public function handle($request, \Closure $next)
    {
        // 检查是否需要认证
        $path = $request->pathinfo();
        if ($this->shouldSkipAuth($path)) {
            return $next($request);
        }

        try {
            // 获取Token
            $token = $request->header('Authorization');

            if (!$token) {
                return $this->errorResponse('未提供认证令牌', 401);
            }

            // 移除 "Bearer " 前缀
            $token = str_replace('Bearer ', '', $token);

            // 验证Token格式
            if (empty($token) || substr_count($token, '.') !== 2) {
                return $this->errorResponse('Token格式错误', 401);
            }

            // 检查Token黑名单
            if ($this->isTokenBlacklisted($token)) {
                return $this->errorResponse('Token已失效', 401);
            }

            // 解析Token
            $payload = JwtHelper::decode($token);

            if ($payload === false) {
                return $this->errorResponse('Token无效或已过期', 401);
            }

            // 验证必要字段
            if (!isset($payload['user_id']) || !isset($payload['tenant_id'])) {
                return $this->errorResponse('Token数据不完整', 401);
            }

            // 设置租户上下文
            TenantContext::setTenantId($payload['tenant_id']);
            TenantContext::setTenantCode($payload['tenant_code'] ?? '');

            // 将用户信息注入到请求对象
            $request->user = [
                'id' => $payload['user_id'],
                'username' => $payload['username'] ?? '',
            ];

            $request->tenantId = $payload['tenant_id'];
            $request->tenantCode = $payload['tenant_code'] ?? '';

            // 记录请求日志（可选）
            if (config('app.log_requests', false)) {
                Log::info('API Request', [
                    'user_id' => $payload['user_id'],
                    'tenant_id' => $payload['tenant_id'],
                    'path' => $path,
                    'method' => $request->method(),
                    'ip' => $request->ip(),
                ]);
            }

            return $next($request);

        } catch (\Throwable $e) {
            Log::error('AuthMiddleware Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('认证失败', 401);
        }
    }

    /**
     * 检查是否应该跳过认证
     *
     * @param string $path
     * @return bool
     */
    protected function shouldSkipAuth(string $path): bool
    {
        foreach ($this->noAuthRequired as $pattern) {
            if (strpos($path, $pattern) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查Token是否在黑名单
     *
     * @param string $token
     * @return bool
     */
    protected function isTokenBlacklisted(string $token): bool
    {
        return Cache::has('token_blacklist:' . md5($token));
    }

    /**
     * 返回错误响应
     *
     * @param string $message
     * @param int $code
     * @return Response
     */
    protected function errorResponse(string $message, int $code = 401): Response
    {
        return json([
            'code' => $code,
            'msg' => $message,
            'data' => null,
        ])->code($code);
    }
}
