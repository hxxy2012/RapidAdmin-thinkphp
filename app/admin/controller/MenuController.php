<?php
namespace app\admin\controller;

use app\admin\service\MenuService;
use app\common\controller\BaseController;
use think\Request;
use think\response\Json;

class MenuController extends BaseController
{
    protected $menuService;

    public function __construct()
    {
        $this->menuService = new MenuService();
    }

    public function tree(Request $request): Json
    {
        try {
            $parentId = $request->param('parent_id', 0);
            $result = $this->menuService->getTree($parentId);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function index(Request $request): Json
    {
        try {
            $where = [];
            if ($request->has('menu_name')) {
                $where['menu_name'] = $request->param('menu_name');
            }
            if ($request->has('menu_type')) {
                $where['menu_type'] = $request->param('menu_type');
            }
            if ($request->has('status')) {
                $where['status'] = $request->param('status');
            }

            $result = $this->menuService->getList($where);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function read(Request $request): Json
    {
        try {
            $menuId = $request->param('id');
            if (!$menuId) return $this->error('菜单ID不能为空');
            $result = $this->menuService->getDetail($menuId);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function save(Request $request): Json
    {
        try {
            $data = $request->only([
                'parent_id', 'menu_name', 'menu_type', 'menu_code',
                'route_path', 'component', 'icon', 'sort', 'visible', 'status', 'is_system'
            ]);
            $this->validateRequired($data, ['menu_name' => '菜单名称', 'menu_type' => '菜单类型']);
            $menuId = $this->menuService->create($data);
            return $this->success('创建成功', ['id' => $menuId]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(Request $request): Json
    {
        try {
            $menuId = $request->param('id');
            if (!$menuId) return $this->error('菜单ID不能为空');
            $data = $request->only([
                'parent_id', 'menu_name', 'menu_type', 'menu_code',
                'route_path', 'component', 'icon', 'sort', 'visible', 'status'
            ]);
            $this->menuService->update($menuId, $data);
            return $this->success('更新成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(Request $request): Json
    {
        try {
            $menuId = $request->param('id');
            if (!$menuId) return $this->error('菜单ID不能为空');
            $this->menuService->delete($menuId);
            return $this->success('删除成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
