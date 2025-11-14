<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 认证业务服务层
// +----------------------------------------------------------------------

namespace app\admin\service;

use app\admin\model\User;
use app\admin\model\Tenant;
use extend\auth\JwtHelper;
use extend\tenant\TenantContext;
use think\facade\Cache;
use think\facade\Log;
use think\facade\Config;
use think\facade\Db;

/**
 * 认证业务服务层
 */
class AuthService
{
    /**
     * 用户登录
     *
     * @param string $username 用户名
     * @param string $password 密码
     * @param string $tenantCode 租户代码
     * @return array
     * @throws \Exception
     */
    public function login(string $username, string $password, string $tenantCode)
    {
        try {
            // 1. 验证租户
            $tenant = Tenant::getByCode($tenantCode);
            if (!$tenant) {
                throw new \Exception('租户不存在');
            }

            if (!$tenant->isAvailable()) {
                $statusText = ['已停用', '正常', '已停用', '已过期'][$tenant->status] ?? '未知';
                throw new \Exception("租户状态异常：{$statusText}");
            }

            // 2. 设置租户上下文
            TenantContext::setTenantId($tenant->id);
            TenantContext::setTenantCode($tenant->tenant_code);

            // 3. 切换到租户数据库
            $this->switchToTenantDatabase($tenant);

            // 4. 查找用户
            $user = User::where('username', $username)
                ->where('tenant_id', $tenant->id)
                ->find();

            if (!$user) {
                // 记录登录失败
                $this->logLoginFail($username, $tenant->id, 'user_not_found');
                throw new \Exception('用户名或密码错误');
            }

            // 5. 验证用户状态
            if ($user->status != 1) {
                $this->logLoginFail($username, $tenant->id, 'user_disabled');
                throw new \Exception('用户已被禁用');
            }

            // 6. 检查登录失败锁定
            if ($this->isLocked($user->id)) {
                throw new \Exception('账号已被锁定，请稍后再试');
            }

            // 7. 验证密码
            if (!$user->verifyPassword($password)) {
                // 记录失败次数
                $this->incrementLoginFails($user->id);
                $this->logLoginFail($username, $tenant->id, 'wrong_password');
                throw new \Exception('用户名或密码错误');
            }

            // 8. 检查密码是否过期
            if ($user->isPasswordExpired()) {
                return [
                    'require_password_change' => true,
                    'user_id' => $user->id,
                    'message' => '密码已过期，请修改密码',
                ];
            }

            // 9. 生成Token
            $token = $this->generateToken($user, $tenant);

            // 10. 更新登录信息
            $user->updateLoginInfo(
                $this->getClientIp(),
                $this->getUserAgent()
            );

            // 11. 清除登录失败记录
            $this->clearLoginFails($user->id);

            // 12. 记录登录日志
            $this->logLoginSuccess($user, $tenant);

            // 13. 获取用户权限和菜单
            $permissions = $this->getUserPermissions($user);
            $menus = $this->getUserMenus($user);

            return [
                'token' => $token,
                'expire_time' => time() + Config::get('app.jwt_expire', 7200),
                'user_info' => [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'realname' => $user->realname,
                    'avatar' => $user->avatar,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'dept_id' => $user->dept_id,
                    'user_type' => $user->user_type,
                ],
                'tenant_info' => [
                    'tenant_id' => $tenant->id,
                    'tenant_code' => $tenant->tenant_code,
                    'tenant_name' => $tenant->tenant_name,
                ],
                'permissions' => $permissions,
                'menus' => $menus,
            ];

        } catch (\Exception $e) {
            Log::error('Login failed: ' . $e->getMessage(), [
                'username' => $username,
                'tenant_code' => $tenantCode,
            ]);

            throw $e;
        }
    }

    /**
     * 退出登录
     *
     * @param string $token
     * @return bool
     */
    public function logout(string $token): bool
    {
        try {
            // 将token加入黑名单
            $this->addTokenToBlacklist($token);

            // 记录登出日志
            $payload = JwtHelper::decode($token);
            if ($payload) {
                Log::info('User logged out', [
                    'user_id' => $payload['user_id'] ?? null,
                    'tenant_id' => $payload['tenant_id'] ?? null,
                ]);
            }

            return true;

        } catch (\Exception $e) {
            Log::error('Logout failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 刷新Token
     *
     * @param string $token
     * @return array
     * @throws \Exception
     */
    public function refreshToken(string $token): array
    {
        // 验证旧token
        $payload = JwtHelper::decode($token);
        if (!$payload) {
            throw new \Exception('Token无效或已过期');
        }

        // 检查是否在黑名单
        if ($this->isTokenBlacklisted($token)) {
            throw new \Exception('Token已失效');
        }

        // 生成新token
        $newToken = JwtHelper::refresh($token, Config::get('app.jwt_expire', 7200));

        if (!$newToken) {
            throw new \Exception('Token刷新失败');
        }

        // 将旧token加入黑名单
        $this->addTokenToBlacklist($token);

        return [
            'token' => $newToken,
            'expire_time' => time() + Config::get('app.jwt_expire', 7200),
        ];
    }

    /**
     * 获取用户信息
     *
     * @param int $userId
     * @param int $tenantId
     * @return array
     * @throws \Exception
     */
    public function getUserInfo(int $userId, int $tenantId): array
    {
        // 设置租户上下文
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            throw new \Exception('租户不存在');
        }

        TenantContext::setTenantId($tenantId);
        $this->switchToTenantDatabase($tenant);

        // 查找用户
        $user = User::with(['dept', 'roles'])->find($userId);
        if (!$user) {
            throw new \Exception('用户不存在');
        }

        // 获取权限和菜单
        $permissions = $this->getUserPermissions($user);
        $menus = $this->getUserMenus($user);

        return [
            'user_info' => [
                'user_id' => $user->id,
                'username' => $user->username,
                'realname' => $user->realname,
                'avatar' => $user->avatar,
                'phone' => $user->phone,
                'email' => $user->email,
                'dept_id' => $user->dept_id,
                'dept_name' => $user->dept->dept_name ?? '',
                'user_type' => $user->user_type,
                'roles' => $user->roles ? $user->roles->toArray() : [],
            ],
            'permissions' => $permissions,
            'menus' => $menus,
        ];
    }

    /**
     * 生成Token
     *
     * @param User $user
     * @param Tenant $tenant
     * @return string
     */
    protected function generateToken(User $user, Tenant $tenant): string
    {
        $payload = [
            'user_id' => $user->id,
            'username' => $user->username,
            'tenant_id' => $tenant->id,
            'tenant_code' => $tenant->tenant_code,
        ];

        $expire = Config::get('app.jwt_expire', 7200);

        return JwtHelper::encode($payload, $expire);
    }

    /**
     * 获取用户权限列表
     *
     * @param User $user
     * @return array
     */
    protected function getUserPermissions(User $user): array
    {
        // TODO: 从角色获取权限列表
        // 这里先返回空数组，在Phase 2中实现完整的权限逻辑
        return [];
    }

    /**
     * 获取用户菜单树
     *
     * @param User $user
     * @return array
     */
    protected function getUserMenus(User $user): array
    {
        // TODO: 从角色获取菜单树
        // 这里先返回空数组，在Phase 2中实现完整的菜单逻辑
        return [];
    }

    /**
     * 切换到租户数据库
     *
     * @param Tenant $tenant
     * @return void
     */
    protected function switchToTenantDatabase(Tenant $tenant)
    {
        $config = Config::get('database.connections.mysql');
        $tenantConfig = array_merge($config, [
            'database' => $tenant->db_name,
            'prefix' => Config::get('database.prefix', 'ea_'),
        ]);

        Config::set(['connections' => ['tenant' => $tenantConfig]], 'database');
    }

    /**
     * 增加登录失败次数
     *
     * @param int $userId
     * @return void
     */
    protected function incrementLoginFails(int $userId)
    {
        $cacheKey = 'login_fails:' . $userId;
        $fails = Cache::get($cacheKey, 0);
        Cache::set($cacheKey, $fails + 1, 1800); // 30分钟过期
    }

    /**
     * 清除登录失败记录
     *
     * @param int $userId
     * @return void
     */
    protected function clearLoginFails(int $userId)
    {
        Cache::delete('login_fails:' . $userId);
    }

    /**
     * 检查是否被锁定
     *
     * @param int $userId
     * @return bool
     */
    protected function isLocked(int $userId): bool
    {
        $cacheKey = 'login_fails:' . $userId;
        $fails = Cache::get($cacheKey, 0);
        $maxFails = Config::get('app.login_fail_limit', 5);

        return $fails >= $maxFails;
    }

    /**
     * 将Token加入黑名单
     *
     * @param string $token
     * @return void
     */
    protected function addTokenToBlacklist(string $token)
    {
        $ttl = JwtHelper::getTTL($token);
        if ($ttl !== false) {
            Cache::set('token_blacklist:' . md5($token), true, $ttl);
        }
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
     * 记录登录成功日志
     *
     * @param User $user
     * @param Tenant $tenant
     * @return void
     */
    protected function logLoginSuccess(User $user, Tenant $tenant)
    {
        try {
            Db::connect('tenant')->table('ea_log_login')->insert([
                'tenant_id' => $tenant->id,
                'user_id' => $user->id,
                'username' => $user->username,
                'ip' => $this->getClientIp(),
                'user_agent' => $this->getUserAgent(),
                'status' => 1,
                'message' => '登录成功',
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log login success: ' . $e->getMessage());
        }
    }

    /**
     * 记录登录失败日志
     *
     * @param string $username
     * @param int $tenantId
     * @param string $reason
     * @return void
     */
    protected function logLoginFail(string $username, int $tenantId, string $reason)
    {
        try {
            Db::connect('tenant')->table('ea_log_login')->insert([
                'tenant_id' => $tenantId,
                'user_id' => 0,
                'username' => $username,
                'ip' => $this->getClientIp(),
                'user_agent' => $this->getUserAgent(),
                'status' => 0,
                'message' => '登录失败：' . $reason,
                'create_time' => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to log login failure: ' . $e->getMessage());
        }
    }

    /**
     * 获取客户端IP
     *
     * @return string
     */
    protected function getClientIp(): string
    {
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            return $_SERVER['REMOTE_ADDR'];
        }

        return 'unknown';
    }

    /**
     * 获取User Agent
     *
     * @return string
     */
    protected function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    }
}
