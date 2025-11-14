<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 权限验证类
// +----------------------------------------------------------------------

namespace extend\auth;

use app\admin\model\User;
use app\admin\model\Role;
use app\admin\model\Permission;
use app\admin\model\Menu;
use app\admin\model\Dept;
use extend\tenant\TenantContext;
use think\facade\Cache;
use think\facade\Log;

/**
 * 权限验证类
 *
 * 实现完整的RBAC权限控制和数据权限过滤
 */
class Auth
{
    /**
     * 当前用户ID
     * @var int
     */
    protected $userId;

    /**
     * 当前租户ID
     * @var int
     */
    protected $tenantId;

    /**
     * 用户信息
     * @var User|null
     */
    protected $user;

    /**
     * 用户角色列表
     * @var array
     */
    protected $roles = [];

    /**
     * 用户权限列表
     * @var array
     */
    protected $permissions = [];

    /**
     * 构造函数
     *
     * @param int $userId
     * @param int|null $tenantId
     */
    public function __construct(int $userId, int $tenantId = null)
    {
        $this->userId = $userId;
        $this->tenantId = $tenantId ?? TenantContext::getTenantId();

        $this->init();
    }

    /**
     * 初始化用户权限数据
     *
     * @return void
     */
    protected function init()
    {
        try {
            // 从缓存获取
            $cacheKey = "auth:user:{$this->userId}:tenant:{$this->tenantId}";
            $cached = Cache::get($cacheKey);

            if ($cached) {
                $this->user = $cached['user'];
                $this->roles = $cached['roles'];
                $this->permissions = $cached['permissions'];
                return;
            }

            // 加载用户信息
            $this->user = User::with(['roles', 'dept'])->find($this->userId);

            if (!$this->user) {
                throw new \Exception('用户不存在');
            }

            // 加载角色
            if ($this->user->roles) {
                $this->roles = $this->user->roles->toArray();
            }

            // 加载权限
            $this->loadPermissions();

            // 缓存1小时
            Cache::set($cacheKey, [
                'user' => $this->user,
                'roles' => $this->roles,
                'permissions' => $this->permissions,
            ], 3600);

        } catch (\Exception $e) {
            Log::error('Auth init failed: ' . $e->getMessage());
        }
    }

    /**
     * 加载用户权限列表
     *
     * @return void
     */
    protected function loadPermissions()
    {
        $permissions = [];

        foreach ($this->roles as $role) {
            // 获取角色的权限
            $rolePermissions = Permission::alias('p')
                ->join('role_permission rp', 'p.id = rp.permission_id')
                ->where('rp.role_id', $role['id'])
                ->where('p.status', 1)
                ->column('p.permission_code');

            $permissions = array_merge($permissions, $rolePermissions);
        }

        $this->permissions = array_unique($permissions);
    }

    /**
     * 检查是否有某个权限
     *
     * @param string $permission 权限标识（支持*通配符）
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        // 超级管理员拥有所有权限
        if ($this->isSuperAdmin()) {
            return true;
        }

        // 检查是否在权限列表中
        if (in_array($permission, $this->permissions)) {
            return true;
        }

        // 支持通配符检查
        // 例如: user.* 可以匹配 user.add, user.edit, user.delete
        foreach ($this->permissions as $userPermission) {
            if ($this->matchWildcard($userPermission, $permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查是否有多个权限中的任意一个
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAnyPermission(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if ($this->hasPermission($permission)) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查是否拥有全部权限
     *
     * @param array $permissions
     * @return bool
     */
    public function hasAllPermissions(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (!$this->hasPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    /**
     * 检查是否有某个角色
     *
     * @param string $roleCode 角色编码
     * @return bool
     */
    public function hasRole(string $roleCode): bool
    {
        foreach ($this->roles as $role) {
            if ($role['role_code'] === $roleCode) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检查是否是超级管理员
     *
     * @return bool
     */
    public function isSuperAdmin(): bool
    {
        return $this->hasRole('admin') || $this->user->user_type == 1;
    }

    /**
     * 获取用户的菜单树
     *
     * @return array
     */
    public function getMenuTree(): array
    {
        // 超级管理员返回所有菜单
        if ($this->isSuperAdmin()) {
            return Menu::getTree(0, $this->tenantId);
        }

        // 获取用户角色的菜单ID
        $menuIds = [];
        foreach ($this->roles as $role) {
            $roleMenuIds = \think\facade\Db::table('ea_role_menu')
                ->where('role_id', $role['id'])
                ->column('menu_id');

            $menuIds = array_merge($menuIds, $roleMenuIds);
        }

        $menuIds = array_unique($menuIds);

        if (empty($menuIds)) {
            return [];
        }

        // 获取菜单数据
        $menus = Menu::whereIn('id', $menuIds)
            ->where('status', 1)
            ->where('visible', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        // 构建树形结构
        return $this->buildMenuTree($menus, 0);
    }

    /**
     * 构建菜单树
     *
     * @param array $menus
     * @param int $parentId
     * @return array
     */
    protected function buildMenuTree(array $menus, int $parentId = 0): array
    {
        $tree = [];

        foreach ($menus as $menu) {
            if ($menu['parent_id'] == $parentId) {
                $menu['children'] = $this->buildMenuTree($menus, $menu['id']);
                $tree[] = $menu;
            }
        }

        return $tree;
    }

    /**
     * 获取用户的权限列表
     *
     * @return array
     */
    public function getPermissions(): array
    {
        return $this->permissions;
    }

    /**
     * 获取用户的角色列表
     *
     * @return array
     */
    public function getRoles(): array
    {
        return $this->roles;
    }

    /**
     * 应用数据权限过滤
     *
     * @param \think\db\Query $query 查询对象
     * @param string $userIdField 用户ID字段名
     * @param string $deptIdField 部门ID字段名
     * @return void
     */
    public function applyDataScope($query, string $userIdField = 'user_id', string $deptIdField = 'dept_id')
    {
        // 超级管理员不需要数据权限过滤
        if ($this->isSuperAdmin()) {
            return;
        }

        // 获取最大的数据权限范围
        $maxDataScope = $this->getMaxDataScope();

        switch ($maxDataScope) {
            case Role::DATA_SCOPE_ALL:
                // 全部数据 - 不需要过滤
                break;

            case Role::DATA_SCOPE_DEPT:
                // 本部门数据
                $query->where($deptIdField, $this->user->dept_id);
                break;

            case Role::DATA_SCOPE_DEPT_AND_CHILD:
                // 本部门及子部门数据
                $deptIds = $this->getDeptAndChildrenIds($this->user->dept_id);
                $query->whereIn($deptIdField, $deptIds);
                break;

            case Role::DATA_SCOPE_SELF:
                // 仅本人数据
                $query->where($userIdField, $this->userId);
                break;

            case Role::DATA_SCOPE_SELF_AND_SUB:
                // 本人及下属数据
                $userIds = $this->getUserAndSubordinateIds();
                $query->whereIn($userIdField, $userIds);
                break;

            case Role::DATA_SCOPE_CUSTOM_DEPT:
                // 自定义部门数据
                $deptIds = $this->getCustomDeptIds();
                if (!empty($deptIds)) {
                    $query->whereIn($deptIdField, $deptIds);
                } else {
                    // 如果没有自定义部门，则只能看自己的
                    $query->where($userIdField, $this->userId);
                }
                break;

            case Role::DATA_SCOPE_CUSTOM_RULE:
                // 自定义规则 - 由业务层实现
                break;

            default:
                // 默认只能看自己的
                $query->where($userIdField, $this->userId);
        }
    }

    /**
     * 获取最大的数据权限范围
     *
     * @return int
     */
    protected function getMaxDataScope(): int
    {
        $maxScope = Role::DATA_SCOPE_SELF; // 默认仅本人

        foreach ($this->roles as $role) {
            $scope = $role['data_scope'] ?? Role::DATA_SCOPE_SELF;

            // 数值越小，权限越大
            if ($scope < $maxScope) {
                $maxScope = $scope;
            }
        }

        return $maxScope;
    }

    /**
     * 获取部门及所有子部门ID
     *
     * @param int $deptId
     * @return array
     */
    protected function getDeptAndChildrenIds(int $deptId): array
    {
        $dept = Dept::find($deptId);
        if (!$dept) {
            return [$deptId];
        }

        return $dept->getChildrenIds();
    }

    /**
     * 获取用户及所有下属用户ID
     *
     * @return array
     */
    protected function getUserAndSubordinateIds(): array
    {
        // 获取用户作为leader的部门
        $deptIds = Dept::where('leader_id', $this->userId)->column('id');

        if (empty($deptIds)) {
            return [$this->userId];
        }

        // 获取这些部门的所有用户
        $userIds = User::whereIn('dept_id', $deptIds)->column('id');
        $userIds[] = $this->userId;

        return array_unique($userIds);
    }

    /**
     * 获取自定义部门ID列表
     *
     * @return array
     */
    protected function getCustomDeptIds(): array
    {
        $deptIds = [];

        foreach ($this->roles as $role) {
            if ($role['data_scope'] == Role::DATA_SCOPE_CUSTOM_DEPT) {
                // 获取角色关联的部门
                $roleDeptIds = \think\facade\Db::table('ea_role_dept')
                    ->where('role_id', $role['id'])
                    ->column('dept_id');

                $deptIds = array_merge($deptIds, $roleDeptIds);
            }
        }

        return array_unique($deptIds);
    }

    /**
     * 通配符匹配
     *
     * @param string $pattern 模式（可包含*通配符）
     * @param string $string 要匹配的字符串
     * @return bool
     */
    protected function matchWildcard(string $pattern, string $string): bool
    {
        // 将*转换为正则表达式
        $pattern = str_replace('.', '\.', $pattern);
        $pattern = str_replace('*', '.*', $pattern);
        $pattern = '/^' . $pattern . '$/';

        return preg_match($pattern, $string) === 1;
    }

    /**
     * 清除用户权限缓存
     *
     * @param int|null $userId
     * @param int|null $tenantId
     * @return void
     */
    public static function clearCache(int $userId = null, int $tenantId = null)
    {
        if ($userId && $tenantId) {
            $cacheKey = "auth:user:{$userId}:tenant:{$tenantId}";
            Cache::delete($cacheKey);
        }
    }

    /**
     * 获取用户信息
     *
     * @return User|null
     */
    public function getUser()
    {
        return $this->user;
    }
}
