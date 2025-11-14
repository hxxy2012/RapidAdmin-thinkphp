<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 角色业务服务层
// +----------------------------------------------------------------------

namespace app\admin\service;

use app\admin\model\Role;
use app\admin\model\Menu;
use app\admin\model\Permission;
use app\admin\model\Dept;
use extend\tenant\TenantContext;
use extend\auth\Auth;
use think\facade\Db;
use think\facade\Log;

/**
 * 角色业务服务层
 */
class RoleService
{
    /**
     * 获取角色列表
     *
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getList(array $where = [], $page = 1, $limit = 15)
    {
        $query = Role::order('sort', 'asc');

        // 角色名称
        if (!empty($where['role_name'])) {
            $query->where('role_name', 'like', '%' . $where['role_name'] . '%');
        }

        // 角色编码
        if (!empty($where['role_code'])) {
            $query->where('role_code', 'like', '%' . $where['role_code'] . '%');
        }

        // 角色类型
        if (!empty($where['role_type'])) {
            $query->where('role_type', $where['role_type']);
        }

        // 状态
        if (isset($where['status']) && $where['status'] !== '') {
            $query->where('status', $where['status']);
        }

        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'list' => $list,
        ];
    }

    /**
     * 获取角色详情
     *
     * @param int $roleId
     * @return array
     * @throws \Exception
     */
    public function getDetail($roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            throw new \Exception('角色不存在');
        }

        $data = $role->toArray();

        // 获取角色的菜单ID列表
        $data['menu_ids'] = Db::table('ea_role_menu')
            ->where('role_id', $roleId)
            ->column('menu_id');

        // 获取角色的权限ID列表
        $data['permission_ids'] = Db::table('ea_role_permission')
            ->where('role_id', $roleId)
            ->column('permission_id');

        // 获取角色的部门ID列表（数据权限）
        $data['dept_ids'] = Db::table('ea_role_dept')
            ->where('role_id', $roleId)
            ->column('dept_id');

        return $data;
    }

    /**
     * 创建角色
     *
     * @param array $data
     * @return int
     * @throws \Exception
     */
    public function create(array $data)
    {
        Db::startTrans();
        try {
            $tenantId = TenantContext::getTenantId();

            // 检查角色编码是否已存在
            if (Role::where('role_code', $data['role_code'])
                    ->where('tenant_id', $tenantId)
                    ->count() > 0) {
                throw new \Exception('角色编码已存在');
            }

            // 创建角色
            $role = new Role();
            $role->tenant_id = $tenantId;
            $role->role_name = $data['role_name'];
            $role->role_code = $data['role_code'];
            $role->role_type = $data['role_type'] ?? Role::TYPE_CUSTOM;
            $role->data_scope = $data['data_scope'] ?? Role::DATA_SCOPE_SELF;
            $role->sort = $data['sort'] ?? 0;
            $role->status = $data['status'] ?? 1;
            $role->remark = $data['remark'] ?? '';
            $role->creator_id = $data['creator_id'] ?? 0;
            $role->save();

            // 分配菜单
            if (!empty($data['menu_ids'])) {
                $this->setMenus($role->id, $data['menu_ids']);
            }

            // 分配权限
            if (!empty($data['permission_ids'])) {
                $this->setPermissions($role->id, $data['permission_ids']);
            }

            // 分配数据权限部门
            if ($data['data_scope'] == Role::DATA_SCOPE_CUSTOM_DEPT && !empty($data['dept_ids'])) {
                $this->setDepts($role->id, $data['dept_ids']);
            }

            Db::commit();

            Log::info('Role created', [
                'role_id' => $role->id,
                'role_code' => $role->role_code,
            ]);

            return $role->id;

        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 更新角色
     *
     * @param int $roleId
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function update($roleId, array $data)
    {
        Db::startTrans();
        try {
            $role = Role::find($roleId);
            if (!$role) {
                throw new \Exception('角色不存在');
            }

            // 系统角色不允许修改某些字段
            if ($role->isSystemRole()) {
                if (isset($data['role_code']) && $data['role_code'] !== $role->role_code) {
                    throw new \Exception('系统角色不允许修改角色编码');
                }
            }

            // 更新允许的字段
            $allowFields = [
                'role_name', 'data_scope', 'sort', 'status', 'remark'
            ];

            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    $role->$field = $data[$field];
                }
            }

            $role->save();

            // 更新菜单
            if (isset($data['menu_ids'])) {
                $this->setMenus($roleId, $data['menu_ids']);
            }

            // 更新权限
            if (isset($data['permission_ids'])) {
                $this->setPermissions($roleId, $data['permission_ids']);
            }

            // 更新数据权限部门
            if (isset($data['dept_ids']) && $role->data_scope == Role::DATA_SCOPE_CUSTOM_DEPT) {
                $this->setDepts($roleId, $data['dept_ids']);
            }

            Db::commit();

            // 清除所有拥有该角色的用户的权限缓存
            $this->clearRoleUsersCache($roleId);

            Log::info('Role updated', ['role_id' => $roleId]);

            return true;

        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 删除角色
     *
     * @param int $roleId
     * @return bool
     * @throws \Exception
     */
    public function delete($roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            throw new \Exception('角色不存在');
        }

        // 检查是否可删除
        if (!$role->canDelete()) {
            if ($role->isSystemRole()) {
                throw new \Exception('系统角色不能删除');
            } else {
                throw new \Exception('该角色下有用户，不能删除');
            }
        }

        Db::startTrans();
        try {
            // 删除角色
            $role->delete();

            // 删除角色关联
            Db::table('ea_role_menu')->where('role_id', $roleId)->delete();
            Db::table('ea_role_permission')->where('role_id', $roleId)->delete();
            Db::table('ea_role_dept')->where('role_id', $roleId)->delete();

            Db::commit();

            Log::info('Role deleted', ['role_id' => $roleId]);

            return true;

        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 设置角色菜单
     *
     * @param int $roleId
     * @param array $menuIds
     * @return bool
     */
    public function setMenus($roleId, array $menuIds)
    {
        // 删除旧的关联
        Db::table('ea_role_menu')->where('role_id', $roleId)->delete();

        // 插入新的关联
        if (!empty($menuIds)) {
            $data = [];
            foreach ($menuIds as $menuId) {
                $data[] = [
                    'role_id' => $roleId,
                    'menu_id' => $menuId,
                ];
            }

            Db::table('ea_role_menu')->insertAll($data);
        }

        // 清除缓存
        $this->clearRoleUsersCache($roleId);

        return true;
    }

    /**
     * 设置角色权限
     *
     * @param int $roleId
     * @param array $permissionIds
     * @return bool
     */
    public function setPermissions($roleId, array $permissionIds)
    {
        // 删除旧的关联
        Db::table('ea_role_permission')->where('role_id', $roleId)->delete();

        // 插入新的关联
        if (!empty($permissionIds)) {
            $data = [];
            foreach ($permissionIds as $permissionId) {
                $data[] = [
                    'role_id' => $roleId,
                    'permission_id' => $permissionId,
                ];
            }

            Db::table('ea_role_permission')->insertAll($data);
        }

        // 清除缓存
        $this->clearRoleUsersCache($roleId);

        return true;
    }

    /**
     * 设置角色数据权限部门
     *
     * @param int $roleId
     * @param array $deptIds
     * @return bool
     */
    public function setDepts($roleId, array $deptIds)
    {
        // 删除旧的关联
        Db::table('ea_role_dept')->where('role_id', $roleId)->delete();

        // 插入新的关联
        if (!empty($deptIds)) {
            $data = [];
            foreach ($deptIds as $deptId) {
                $data[] = [
                    'role_id' => $roleId,
                    'dept_id' => $deptId,
                ];
            }

            Db::table('ea_role_dept')->insertAll($data);
        }

        // 清除缓存
        $this->clearRoleUsersCache($roleId);

        return true;
    }

    /**
     * 获取角色的菜单树
     *
     * @param int $roleId
     * @return array
     */
    public function getMenuTree($roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            return [];
        }

        $menuIds = Db::table('ea_role_menu')
            ->where('role_id', $roleId)
            ->column('menu_id');

        if (empty($menuIds)) {
            return [];
        }

        $menus = Menu::whereIn('id', $menuIds)
            ->where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

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
     * 清除角色下所有用户的权限缓存
     *
     * @param int $roleId
     * @return void
     */
    protected function clearRoleUsersCache($roleId)
    {
        $tenantId = TenantContext::getTenantId();

        // 获取拥有该角色的所有用户ID
        $userIds = Db::table('ea_user_role')
            ->where('role_id', $roleId)
            ->column('user_id');

        // 清除每个用户的权限缓存
        foreach ($userIds as $userId) {
            Auth::clearCache($userId, $tenantId);
        }
    }

    /**
     * 启用角色
     *
     * @param int $roleId
     * @return bool
     * @throws \Exception
     */
    public function enable($roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            throw new \Exception('角色不存在');
        }

        $role->status = 1;
        $result = $role->save();

        // 清除缓存
        $this->clearRoleUsersCache($roleId);

        return $result !== false;
    }

    /**
     * 禁用角色
     *
     * @param int $roleId
     * @return bool
     * @throws \Exception
     */
    public function disable($roleId)
    {
        $role = Role::find($roleId);
        if (!$role) {
            throw new \Exception('角色不存在');
        }

        // 系统角色不能禁用
        if ($role->isSystemRole()) {
            throw new \Exception('系统角色不能禁用');
        }

        $role->status = 0;
        $result = $role->save();

        // 清除缓存
        $this->clearRoleUsersCache($roleId);

        return $result !== false;
    }
}
