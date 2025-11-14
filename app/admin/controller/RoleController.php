<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 角色管理控制器
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\admin\service\RoleService;
use app\common\controller\BaseController;
use think\Request;
use think\response\Json;

/**
 * 角色管理控制器
 */
class RoleController extends BaseController
{
    /**
     * @var RoleService
     */
    protected $roleService;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->roleService = new RoleService();
    }

    /**
     * 角色列表
     *
     * @param Request $request
     * @return Json
     */
    public function index(Request $request): Json
    {
        try {
            $where = [];

            if ($request->has('role_name')) {
                $where['role_name'] = $request->param('role_name');
            }

            if ($request->has('role_code')) {
                $where['role_code'] = $request->param('role_code');
            }

            if ($request->has('role_type')) {
                $where['role_type'] = $request->param('role_type');
            }

            if ($request->has('status')) {
                $where['status'] = $request->param('status');
            }

            $page = $request->param('page', 1);
            $limit = $request->param('limit', 15);

            $result = $this->roleService->getList($where, $page, $limit);

            return $this->success('获取成功', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 角色详情
     *
     * @param Request $request
     * @return Json
     */
    public function read(Request $request): Json
    {
        try {
            $roleId = $request->param('id');
            if (!$roleId) {
                return $this->error('角色ID不能为空');
            }

            $result = $this->roleService->getDetail($roleId);

            return $this->success('获取成功', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 创建角色
     *
     * @param Request $request
     * @return Json
     */
    public function save(Request $request): Json
    {
        try {
            $data = $request->only([
                'role_name',
                'role_code',
                'role_type',
                'data_scope',
                'sort',
                'status',
                'remark',
                'menu_ids',
                'permission_ids',
                'dept_ids',
            ]);

            // 验证必填字段
            $this->validateRequired($data, [
                'role_name' => '角色名称',
                'role_code' => '角色编码',
            ]);

            // 验证角色编码格式
            if (!preg_match('/^[a-z0-9_]{3,20}$/', $data['role_code'])) {
                return $this->error('角色编码格式错误，只允许小写字母、数字、下划线，长度3-20位');
            }

            // 添加创建者ID
            $data['creator_id'] = $this->getUserId();

            // 处理数组参数
            foreach (['menu_ids', 'permission_ids', 'dept_ids'] as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    $data[$field] = json_decode($data[$field], true);
                }
            }

            $roleId = $this->roleService->create($data);

            return $this->success('创建成功', ['id' => $roleId]);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 更新角色
     *
     * @param Request $request
     * @return Json
     */
    public function update(Request $request): Json
    {
        try {
            $roleId = $request->param('id');
            if (!$roleId) {
                return $this->error('角色ID不能为空');
            }

            $data = $request->only([
                'role_name',
                'data_scope',
                'sort',
                'status',
                'remark',
                'menu_ids',
                'permission_ids',
                'dept_ids',
            ]);

            // 处理数组参数
            foreach (['menu_ids', 'permission_ids', 'dept_ids'] as $field) {
                if (isset($data[$field]) && is_string($data[$field])) {
                    $data[$field] = json_decode($data[$field], true);
                }
            }

            $this->roleService->update($roleId, $data);

            return $this->success('更新成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除角色
     *
     * @param Request $request
     * @return Json
     */
    public function delete(Request $request): Json
    {
        try {
            $roleId = $request->param('id');
            if (!$roleId) {
                return $this->error('角色ID不能为空');
            }

            $this->roleService->delete($roleId);

            return $this->success('删除成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 设置角色菜单
     *
     * @param Request $request
     * @return Json
     */
    public function setMenus(Request $request): Json
    {
        try {
            $roleId = $request->param('role_id');
            $menuIds = $request->param('menu_ids', []);

            if (!$roleId) {
                return $this->error('角色ID不能为空');
            }

            // 处理JSON字符串
            if (is_string($menuIds)) {
                $menuIds = json_decode($menuIds, true);
            }

            $this->roleService->setMenus($roleId, $menuIds);

            return $this->success('设置成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 设置角色权限
     *
     * @param Request $request
     * @return Json
     */
    public function setPermissions(Request $request): Json
    {
        try {
            $roleId = $request->param('role_id');
            $permissionIds = $request->param('permission_ids', []);

            if (!$roleId) {
                return $this->error('角色ID不能为空');
            }

            // 处理JSON字符串
            if (is_string($permissionIds)) {
                $permissionIds = json_decode($permissionIds, true);
            }

            $this->roleService->setPermissions($roleId, $permissionIds);

            return $this->success('设置成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取角色菜单树
     *
     * @param Request $request
     * @return Json
     */
    public function getMenuTree(Request $request): Json
    {
        try {
            $roleId = $request->param('role_id');

            if (!$roleId) {
                return $this->error('角色ID不能为空');
            }

            $result = $this->roleService->getMenuTree($roleId);

            return $this->success('获取成功', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 启用角色
     *
     * @param Request $request
     * @return Json
     */
    public function enable(Request $request): Json
    {
        try {
            $roleId = $request->param('id');
            if (!$roleId) {
                return $this->error('角色ID不能为空');
            }

            $this->roleService->enable($roleId);

            return $this->success('启用成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 禁用角色
     *
     * @param Request $request
     * @return Json
     */
    public function disable(Request $request): Json
    {
        try {
            $roleId = $request->param('id');
            if (!$roleId) {
                return $this->error('角色ID不能为空');
            }

            $this->roleService->disable($roleId);

            return $this->success('禁用成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
