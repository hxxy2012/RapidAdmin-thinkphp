<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 部门管理控制器
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\admin\service\DeptService;
use app\common\controller\BaseController;
use think\Request;
use think\response\Json;

/**
 * 部门管理控制器
 */
class DeptController extends BaseController
{
    protected $deptService;

    public function __construct()
    {
        $this->deptService = new DeptService();
    }

    /**
     * 获取部门树
     */
    public function tree(Request $request): Json
    {
        try {
            $parentId = $request->param('parent_id', 0);
            $result = $this->deptService->getTree($parentId);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 部门列表
     */
    public function index(Request $request): Json
    {
        try {
            $where = [];
            if ($request->has('dept_name')) {
                $where['dept_name'] = $request->param('dept_name');
            }
            if ($request->has('dept_type')) {
                $where['dept_type'] = $request->param('dept_type');
            }
            if ($request->has('status')) {
                $where['status'] = $request->param('status');
            }

            $result = $this->deptService->getList($where);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 部门详情
     */
    public function read(Request $request): Json
    {
        try {
            $deptId = $request->param('id');
            if (!$deptId) {
                return $this->error('部门ID不能为空');
            }
            $result = $this->deptService->getDetail($deptId);
            return $this->success('获取成功', $result);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 创建部门
     */
    public function save(Request $request): Json
    {
        try {
            $data = $request->only([
                'parent_id', 'dept_name', 'dept_code', 'dept_type',
                'leader_id', 'sort', 'status', 'remark'
            ]);

            $this->validateRequired($data, [
                'dept_name' => '部门名称',
                'dept_code' => '部门编码',
            ]);

            $deptId = $this->deptService->create($data);
            return $this->success('创建成功', ['id' => $deptId]);
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 更新部门
     */
    public function update(Request $request): Json
    {
        try {
            $deptId = $request->param('id');
            if (!$deptId) {
                return $this->error('部门ID不能为空');
            }

            $data = $request->only([
                'parent_id', 'dept_name', 'dept_type',
                'leader_id', 'sort', 'status', 'remark'
            ]);

            $this->deptService->update($deptId, $data);
            return $this->success('更新成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除部门
     */
    public function delete(Request $request): Json
    {
        try {
            $deptId = $request->param('id');
            if (!$deptId) {
                return $this->error('部门ID不能为空');
            }

            $this->deptService->delete($deptId);
            return $this->success('删除成功');
        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
