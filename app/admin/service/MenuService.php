<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 菜单业务服务层
// +----------------------------------------------------------------------

namespace app\admin\service;

use app\admin\model\Menu;
use extend\tenant\TenantContext;
use think\facade\Db;
use think\facade\Log;
use extend\auth\Auth;

class MenuService
{
    /**
     * 获取菜单树
     */
    public function getTree($parentId = 0, $tenantId = null)
    {
        $tenantId = $tenantId ?? TenantContext::getTenantId();
        return Menu::getTree($parentId, $tenantId);
    }

    /**
     * 获取菜单列表
     */
    public function getList(array $where = [])
    {
        $query = Menu::with(['parent']);

        if (!empty($where['menu_name'])) {
            $query->where('menu_name', 'like', '%' . $where['menu_name'] . '%');
        }
        if (!empty($where['menu_type'])) {
            $query->where('menu_type', $where['menu_type']);
        }
        if (isset($where['status']) && $where['status'] !== '') {
            $query->where('status', $where['status']);
        }

        return $query->order('sort', 'asc')->select()->toArray();
    }

    /**
     * 获取菜单详情
     */
    public function getDetail($menuId)
    {
        $menu = Menu::with(['parent'])->find($menuId);
        if (!$menu) {
            throw new \Exception('菜单不存在');
        }
        return $menu->toArray();
    }

    /**
     * 创建菜单
     */
    public function create(array $data)
    {
        try {
            $tenantId = TenantContext::getTenantId();

            $menu = new Menu();
            $menu->tenant_id = $data['is_system'] ?? false ? null : $tenantId;
            $menu->parent_id = $data['parent_id'] ?? 0;
            $menu->menu_name = $data['menu_name'];
            $menu->menu_type = $data['menu_type'];
            $menu->menu_code = $data['menu_code'] ?? '';
            $menu->route_path = $data['route_path'] ?? '';
            $menu->component = $data['component'] ?? '';
            $menu->icon = $data['icon'] ?? '';
            $menu->sort = $data['sort'] ?? 0;
            $menu->visible = $data['visible'] ?? 1;
            $menu->status = $data['status'] ?? 1;
            $menu->save();

            Log::info('Menu created', ['menu_id' => $menu->id]);
            return $menu->id;
        } catch (\Exception $e) {
            Log::error('Menu creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 更新菜单
     */
    public function update($menuId, array $data)
    {
        try {
            $menu = Menu::find($menuId);
            if (!$menu) {
                throw new \Exception('菜单不存在');
            }

            if (isset($data['parent_id']) && $data['parent_id'] != 0) {
                if ($data['parent_id'] == $menuId) {
                    throw new \Exception('不能将菜单设置为自己的上级');
                }
            }

            $allowFields = [
                'parent_id', 'menu_name', 'menu_type', 'menu_code',
                'route_path', 'component', 'icon', 'sort', 'visible', 'status'
            ];

            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    $menu->$field = $data[$field];
                }
            }

            $result = $menu->save();

            // 清除相关角色用户的权限缓存
            $this->clearRelatedCache($menuId);

            Log::info('Menu updated', ['menu_id' => $menuId]);
            return $result !== false;
        } catch (\Exception $e) {
            Log::error('Menu update failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 删除菜单
     */
    public function delete($menuId)
    {
        $menu = Menu::find($menuId);
        if (!$menu) {
            throw new \Exception('菜单不存在');
        }

        if ($menu->hasChildren()) {
            throw new \Exception('该菜单下有子菜单，不能删除');
        }

        Db::startTrans();
        try {
            $menu->delete();
            Db::table('ea_role_menu')->where('menu_id', $menuId)->delete();

            Db::commit();

            $this->clearRelatedCache($menuId);
            Log::info('Menu deleted', ['menu_id' => $menuId]);

            return true;
        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 清除相关缓存
     */
    protected function clearRelatedCache($menuId)
    {
        $tenantId = TenantContext::getTenantId();
        $roleIds = Db::table('ea_role_menu')->where('menu_id', $menuId)->column('role_id');

        foreach ($roleIds as $roleId) {
            $userIds = Db::table('ea_user_role')->where('role_id', $roleId)->column('user_id');
            foreach ($userIds as $userId) {
                Auth::clearCache($userId, $tenantId);
            }
        }
    }
}
