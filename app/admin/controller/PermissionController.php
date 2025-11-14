<?php
namespace app\admin\controller;

use app\admin\service\PermissionService;
use app\common\controller\BaseController;
use think\Request;
use think\response\Json;

class PermissionController extends BaseController
{
    protected $permissionService;

    public function __construct()
    {
        $this->permissionService = new PermissionService();
    }

    public function tree(Request $request): Json
    {
        try {
            $parentId = $request->param('parent_id', 0);
            $result = $this->permissionService->getTree($parentId);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function index(Request $request): Json
    {
        try {
            $where = [];
            if ($request->has('permission_name')) $where['permission_name'] = $request->param('permission_name');
            if ($request->has('permission_type')) $where['permission_type'] = $request->param('permission_type');
            if ($request->has('status')) $where['status'] = $request->param('status');
            $result = $this->permissionService->getList($where);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function read(Request $request): Json
    {
        try {
            $permissionId = $request->param('id');
            if (!$permissionId) return $this->error('权限ID不能为空');
            $result = $this->permissionService->getDetail($permissionId);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function save(Request $request): Json
    {
        try {
            $data = $request->only(['parent_id', 'permission_name', 'permission_code', 'permission_type', 'sort', 'status', 'is_system']);
            $this->validateRequired($data, ['permission_name' => '权限名称', 'permission_code' => '权限编码', 'permission_type' => '权限类型']);
            $permissionId = $this->permissionService->create($data);
            return $this->success('创建成功', ['id' => $permissionId]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function update(Request $request): Json
    {
        try {
            $permissionId = $request->param('id');
            if (!$permissionId) return $this->error('权限ID不能为空');
            $data = $request->only(['parent_id', 'permission_name', 'permission_code', 'permission_type', 'sort', 'status']);
            $this->permissionService->update($permissionId, $data);
            return $this->success('更新成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    public function delete(Request $request): Json
    {
        try {
            $permissionId = $request->param('id');
            if (!$permissionId) return $this->error('权限ID不能为空');
            $this->permissionService->delete($permissionId);
            return $this->success('删除成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
