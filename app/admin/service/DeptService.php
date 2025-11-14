<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 部门业务服务层
// +----------------------------------------------------------------------

namespace app\admin\service;

use app\admin\model\Dept;
use app\admin\model\User;
use extend\tenant\TenantContext;
use think\facade\Db;
use think\facade\Log;

/**
 * 部门业务服务层
 */
class DeptService
{
    /**
     * 获取部门树
     *
     * @param int $parentId
     * @return array
     */
    public function getTree($parentId = 0)
    {
        $depts = Dept::where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        return $this->buildTree($depts, $parentId);
    }

    /**
     * 获取部门列表
     *
     * @param array $where
     * @return array
     */
    public function getList(array $where = [])
    {
        $query = Dept::with(['parent', 'leader']);

        if (!empty($where['dept_name'])) {
            $query->where('dept_name', 'like', '%' . $where['dept_name'] . '%');
        }

        if (!empty($where['dept_type'])) {
            $query->where('dept_type', $where['dept_type']);
        }

        if (isset($where['status']) && $where['status'] !== '') {
            $query->where('status', $where['status']);
        }

        $list = $query->order('sort', 'asc')->select()->toArray();

        return $list;
    }

    /**
     * 获取部门详情
     *
     * @param int $deptId
     * @return array
     * @throws \Exception
     */
    public function getDetail($deptId)
    {
        $dept = Dept::with(['parent', 'leader'])->find($deptId);
        if (!$dept) {
            throw new \Exception('部门不存在');
        }

        return $dept->toArray();
    }

    /**
     * 创建部门
     *
     * @param array $data
     * @return int
     * @throws \Exception
     */
    public function create(array $data)
    {
        try {
            $tenantId = TenantContext::getTenantId();

            // 检查部门编码是否已存在
            if (Dept::where('dept_code', $data['dept_code'])
                    ->where('tenant_id', $tenantId)
                    ->count() > 0) {
                throw new \Exception('部门编码已存在');
            }

            $dept = new Dept();
            $dept->tenant_id = $tenantId;
            $dept->parent_id = $data['parent_id'] ?? 0;
            $dept->dept_name = $data['dept_name'];
            $dept->dept_code = $data['dept_code'];
            $dept->dept_type = $data['dept_type'] ?? 4;
            $dept->leader_id = $data['leader_id'] ?? 0;
            $dept->sort = $data['sort'] ?? 0;
            $dept->status = $data['status'] ?? 1;
            $dept->remark = $data['remark'] ?? '';
            $dept->save();

            Log::info('Dept created', [
                'dept_id' => $dept->id,
                'dept_code' => $dept->dept_code,
            ]);

            return $dept->id;

        } catch (\Exception $e) {
            Log::error('Dept creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 更新部门
     *
     * @param int $deptId
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function update($deptId, array $data)
    {
        try {
            $dept = Dept::find($deptId);
            if (!$dept) {
                throw new \Exception('部门不存在');
            }

            // 不能将部门设置为自己的子部门
            if (isset($data['parent_id']) && $data['parent_id'] != 0) {
                $childIds = $dept->getChildrenIds();
                if (in_array($data['parent_id'], $childIds) || $data['parent_id'] == $deptId) {
                    throw new \Exception('不能将部门设置为自己或子部门的下级');
                }
            }

            $allowFields = [
                'parent_id', 'dept_name', 'dept_type', 'leader_id',
                'sort', 'status', 'remark'
            ];

            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    $dept->$field = $data[$field];
                }
            }

            $result = $dept->save();

            Log::info('Dept updated', ['dept_id' => $deptId]);

            return $result !== false;

        } catch (\Exception $e) {
            Log::error('Dept update failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 删除部门
     *
     * @param int $deptId
     * @return bool
     * @throws \Exception
     */
    public function delete($deptId)
    {
        $dept = Dept::find($deptId);
        if (!$dept) {
            throw new \Exception('部门不存在');
        }

        // 检查是否有子部门
        if ($dept->hasChildren()) {
            throw new \Exception('该部门下有子部门，不能删除');
        }

        // 检查是否有用户
        $userCount = User::where('dept_id', $deptId)->count();
        if ($userCount > 0) {
            throw new \Exception('该部门下有用户，不能删除');
        }

        $result = $dept->delete();

        Log::info('Dept deleted', ['dept_id' => $deptId]);

        return $result !== false;
    }

    /**
     * 构建树形结构
     *
     * @param array $depts
     * @param int $parentId
     * @return array
     */
    protected function buildTree(array $depts, int $parentId = 0): array
    {
        $tree = [];

        foreach ($depts as $dept) {
            if ($dept['parent_id'] == $parentId) {
                $dept['children'] = $this->buildTree($depts, $dept['id']);
                $tree[] = $dept;
            }
        }

        return $tree;
    }
}
