<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 权限验证中间件
// +----------------------------------------------------------------------

namespace app\admin\middleware;

use extend\auth\Auth;
use think\facade\Log;
use think\Response;

/**
 * 权限验证中间件
 *
 * 验证用户是否拥有访问当前路由的权限
 */
class PermissionMiddleware
{
    /**
     * 不需要权限验证的路由
     * @var array
     */
    protected $noPermissionRequired = [
        '/admin/auth/login',
        '/admin/auth/logout',
        '/admin/auth/refresh',
        '/admin/auth/userInfo',
        '/admin/auth/captcha',
        '/admin/auth/changePassword',
    ];

    /**
     * 路由权限映射表
     *
     * 将路由路径映射到权限标识
     * 例如: '/admin/user/index' => 'user.list'
     *
     * @var array
     */
    protected $routePermissionMap = [
        // 用户管理
        '/admin/user/index' => 'user.list',
        '/admin/user/read' => 'user.view',
        '/admin/user/save' => 'user.add',
        '/admin/user/update' => 'user.edit',
        '/admin/user/delete' => 'user.delete',
        '/admin/user/enable' => 'user.enable',
        '/admin/user/disable' => 'user.disable',
        '/admin/user/resetPassword' => 'user.reset_password',
        '/admin/user/import' => 'user.import',
        '/admin/user/export' => 'user.export',

        // 角色管理
        '/admin/role/index' => 'role.list',
        '/admin/role/read' => 'role.view',
        '/admin/role/save' => 'role.add',
        '/admin/role/update' => 'role.edit',
        '/admin/role/delete' => 'role.delete',
        '/admin/role/setPermissions' => 'role.permission',
        '/admin/role/setMenus' => 'role.menu',

        // 部门管理
        '/admin/dept/index' => 'dept.list',
        '/admin/dept/read' => 'dept.view',
        '/admin/dept/save' => 'dept.add',
        '/admin/dept/update' => 'dept.edit',
        '/admin/dept/delete' => 'dept.delete',

        // 菜单管理
        '/admin/menu/index' => 'menu.list',
        '/admin/menu/read' => 'menu.view',
        '/admin/menu/save' => 'menu.add',
        '/admin/menu/update' => 'menu.edit',
        '/admin/menu/delete' => 'menu.delete',

        // 权限管理
        '/admin/permission/index' => 'permission.list',
        '/admin/permission/read' => 'permission.view',
        '/admin/permission/save' => 'permission.add',
        '/admin/permission/update' => 'permission.edit',
        '/admin/permission/delete' => 'permission.delete',

        // 租户管理（仅平台管理员）
        '/admin/tenant/index' => 'tenant.list',
        '/admin/tenant/read' => 'tenant.view',
        '/admin/tenant/save' => 'tenant.add',
        '/admin/tenant/update' => 'tenant.edit',
        '/admin/tenant/enable' => 'tenant.enable',
        '/admin/tenant/disable' => 'tenant.disable',
        '/admin/tenant/renew' => 'tenant.renew',

        // 套餐管理（仅平台管理员）
        '/admin/package/index' => 'package.list',
        '/admin/package/read' => 'package.view',
        '/admin/package/save' => 'package.add',
        '/admin/package/update' => 'package.edit',
        '/admin/package/delete' => 'package.delete',
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
        // 检查是否需要权限验证
        $path = $request->pathinfo();

        if ($this->shouldSkipPermission($path)) {
            return $next($request);
        }

        try {
            // 获取用户信息（由AuthMiddleware注入）
            $userId = $request->user['id'] ?? null;
            $tenantId = $request->tenantId ?? null;

            if (!$userId || !$tenantId) {
                return $this->errorResponse('用户信息获取失败', 401);
            }

            // 创建Auth实例
            $auth = new Auth($userId, $tenantId);

            // 获取当前路由需要的权限
            $requiredPermission = $this->getRequiredPermission($path, $request);

            // 检查权限
            if ($requiredPermission && !$auth->hasPermission($requiredPermission)) {
                Log::warning('Permission denied', [
                    'user_id' => $userId,
                    'path' => $path,
                    'required_permission' => $requiredPermission,
                ]);

                return $this->errorResponse('无权限访问', 403);
            }

            // 将Auth实例注入到请求对象，供控制器使用
            $request->auth = $auth;

            return $next($request);

        } catch (\Throwable $e) {
            Log::error('PermissionMiddleware Error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('权限验证失败', 500);
        }
    }

    /**
     * 检查是否应该跳过权限验证
     *
     * @param string $path
     * @return bool
     */
    protected function shouldSkipPermission(string $path): bool
    {
        foreach ($this->noPermissionRequired as $pattern) {
            if (strpos($path, $pattern) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * 获取当前路由需要的权限
     *
     * @param string $path
     * @param \think\Request $request
     * @return string|null
     */
    protected function getRequiredPermission(string $path, $request): ?string
    {
        // 1. 从路由映射表获取
        if (isset($this->routePermissionMap[$path])) {
            return $this->routePermissionMap[$path];
        }

        // 2. 从路由参数获取（可以在路由定义中指定permission参数）
        $permission = $request->param('_permission');
        if ($permission) {
            return $permission;
        }

        // 3. 从注解获取（需要配合注解功能）
        // TODO: 实现注解权限

        // 4. 自动生成权限标识
        // 例如: /admin/user/index => user.list
        return $this->generatePermission($path);
    }

    /**
     * 自动生成权限标识
     *
     * @param string $path
     * @return string|null
     */
    protected function generatePermission(string $path): ?string
    {
        // 匹配 /admin/{controller}/{action} 格式
        if (preg_match('#^/admin/([a-z]+)/([a-z]+)#i', $path, $matches)) {
            $controller = strtolower($matches[1]);
            $action = strtolower($matches[2]);

            // 操作名称映射
            $actionMap = [
                'index' => 'list',
                'read' => 'view',
                'save' => 'add',
                'update' => 'edit',
                'delete' => 'delete',
            ];

            $permission = $actionMap[$action] ?? $action;

            return "{$controller}.{$permission}";
        }

        return null;
    }

    /**
     * 返回错误响应
     *
     * @param string $message
     * @param int $code
     * @return Response
     */
    protected function errorResponse(string $message, int $code = 403): Response
    {
        return json([
            'code' => $code,
            'msg' => $message,
            'data' => null,
        ])->code($code);
    }
}
