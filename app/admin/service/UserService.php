<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 用户业务服务层
// +----------------------------------------------------------------------

namespace app\admin\service;

use app\admin\model\User;
use app\admin\model\Dept;
use app\admin\model\Role;
use extend\tenant\TenantContext;
use extend\auth\Auth;
use think\facade\Db;
use think\facade\Log;

/**
 * 用户业务服务层
 */
class UserService
{
    /**
     * 获取用户列表
     *
     * @param array $where
     * @param int $page
     * @param int $limit
     * @param Auth|null $auth
     * @return array
     */
    public function getList(array $where = [], $page = 1, $limit = 15, Auth $auth = null)
    {
        $query = User::with(['dept', 'roles']);

        // 应用数据权限过滤
        if ($auth) {
            $auth->applyDataScope($query, 'id', 'dept_id');
        }

        // 用户名
        if (!empty($where['username'])) {
            $query->where('username', 'like', '%' . $where['username'] . '%');
        }

        // 真实姓名
        if (!empty($where['realname'])) {
            $query->where('realname', 'like', '%' . $where['realname'] . '%');
        }

        // 手机号
        if (!empty($where['phone'])) {
            $query->where('phone', 'like', '%' . $where['phone'] . '%');
        }

        // 部门ID
        if (!empty($where['dept_id'])) {
            $query->where('dept_id', $where['dept_id']);
        }

        // 状态
        if (isset($where['status']) && $where['status'] !== '') {
            $query->where('status', $where['status']);
        }

        // 用户类型
        if (!empty($where['user_type'])) {
            $query->where('user_type', $where['user_type']);
        }

        $total = $query->count();
        $list = $query->page($page, $limit)
                     ->order('id', 'desc')
                     ->select()
                     ->toArray();

        return [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'list' => $list,
        ];
    }

    /**
     * 获取用户详情
     *
     * @param int $userId
     * @return array
     * @throws \Exception
     */
    public function getDetail($userId)
    {
        $user = User::with(['dept', 'roles'])->find($userId);
        if (!$user) {
            throw new \Exception('用户不存在');
        }

        $data = $user->toArray();

        // 获取角色ID列表
        $data['role_ids'] = $user->roles ? array_column($user->roles->toArray(), 'id') : [];

        return $data;
    }

    /**
     * 创建用户
     *
     * @param array $data
     * @param TenantService|null $tenantService
     * @return int
     * @throws \Exception
     */
    public function create(array $data, TenantService $tenantService = null)
    {
        Db::startTrans();
        try {
            // 检查用户名是否已存在
            $tenantId = TenantContext::getTenantId();
            if (User::where('username', $data['username'])
                    ->where('tenant_id', $tenantId)
                    ->count() > 0) {
                throw new \Exception('用户名已存在');
            }

            // 检查手机号是否已存在
            if (!empty($data['phone'])) {
                if (User::where('phone', $data['phone'])
                        ->where('tenant_id', $tenantId)
                        ->count() > 0) {
                    throw new \Exception('手机号已被使用');
                }
            }

            // 检查配额
            if ($tenantService) {
                $tenantService->checkQuota($tenantId, 'users', 1);
            }

            // 创建用户
            $user = new User();
            $user->tenant_id = $tenantId;
            $user->username = $data['username'];
            $user->password = password_hash($data['password'], PASSWORD_BCRYPT);
            $user->realname = $data['realname'];
            $user->phone = $data['phone'] ?? '';
            $user->email = $data['email'] ?? '';
            $user->dept_id = $data['dept_id'] ?? 0;
            $user->leader_id = $data['leader_id'] ?? 0;
            $user->user_type = $data['user_type'] ?? 2;
            $user->gender = $data['gender'] ?? 0;
            $user->avatar = $data['avatar'] ?? '';
            $user->status = $data['status'] ?? 1;
            $user->remark = $data['remark'] ?? '';
            $user->save();

            // 分配角色
            if (!empty($data['role_ids'])) {
                $this->assignRoles($user->id, $data['role_ids']);
            }

            // 更新租户用户配额
            if ($tenantService) {
                $tenantService->updateQuota($tenantId, 'users', 1);
            }

            Db::commit();

            // 清除权限缓存
            Auth::clearCache($user->id, $tenantId);

            Log::info('User created', [
                'user_id' => $user->id,
                'username' => $user->username,
            ]);

            return $user->id;

        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 更新用户
     *
     * @param int $userId
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function update($userId, array $data)
    {
        Db::startTrans();
        try {
            $user = User::find($userId);
            if (!$user) {
                throw new \Exception('用户不存在');
            }

            $tenantId = TenantContext::getTenantId();

            // 检查手机号是否被其他用户使用
            if (!empty($data['phone']) && $data['phone'] !== $user->phone) {
                if (User::where('phone', $data['phone'])
                        ->where('tenant_id', $tenantId)
                        ->where('id', '<>', $userId)
                        ->count() > 0) {
                    throw new \Exception('手机号已被使用');
                }
            }

            // 更新允许的字段
            $allowFields = [
                'realname', 'phone', 'email', 'dept_id', 'leader_id',
                'gender', 'avatar', 'status', 'remark'
            ];

            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    $user->$field = $data[$field];
                }
            }

            $user->save();

            // 更新角色
            if (isset($data['role_ids'])) {
                $this->assignRoles($userId, $data['role_ids']);
            }

            Db::commit();

            // 清除权限缓存
            Auth::clearCache($userId, $tenantId);

            Log::info('User updated', ['user_id' => $userId]);

            return true;

        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 删除用户
     *
     * @param int $userId
     * @param TenantService|null $tenantService
     * @return bool
     * @throws \Exception
     */
    public function delete($userId, TenantService $tenantService = null)
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \Exception('用户不存在');
        }

        // 不能删除管理员
        if ($user->user_type == 1) {
            throw new \Exception('不能删除管理员账号');
        }

        $tenantId = TenantContext::getTenantId();

        // 软删除
        $result = $user->delete();

        if ($result) {
            // 删除用户角色关联
            Db::table('ea_user_role')->where('user_id', $userId)->delete();

            // 更新租户用户配额
            if ($tenantService) {
                $tenantService->updateQuota($tenantId, 'users', -1);
            }

            // 清除权限缓存
            Auth::clearCache($userId, $tenantId);

            Log::info('User deleted', ['user_id' => $userId]);
        }

        return $result !== false;
    }

    /**
     * 重置密码
     *
     * @param int $userId
     * @param string $newPassword
     * @return bool
     * @throws \Exception
     */
    public function resetPassword($userId, $newPassword = '123456')
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \Exception('用户不存在');
        }

        $user->password = password_hash($newPassword, PASSWORD_BCRYPT);
        $user->password_update_time = date('Y-m-d H:i:s');

        $result = $user->save();

        Log::info('Password reset', ['user_id' => $userId]);

        return $result !== false;
    }

    /**
     * 修改密码
     *
     * @param int $userId
     * @param string $oldPassword
     * @param string $newPassword
     * @return bool
     * @throws \Exception
     */
    public function changePassword($userId, $oldPassword, $newPassword)
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \Exception('用户不存在');
        }

        // 验证旧密码
        if (!$user->verifyPassword($oldPassword)) {
            throw new \Exception('原密码错误');
        }

        $user->password = password_hash($newPassword, PASSWORD_BCRYPT);
        $user->password_update_time = date('Y-m-d H:i:s');

        $result = $user->save();

        Log::info('Password changed', ['user_id' => $userId]);

        return $result !== false;
    }

    /**
     * 启用用户
     *
     * @param int $userId
     * @return bool
     * @throws \Exception
     */
    public function enable($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \Exception('用户不存在');
        }

        $user->status = 1;
        return $user->save() !== false;
    }

    /**
     * 禁用用户
     *
     * @param int $userId
     * @return bool
     * @throws \Exception
     */
    public function disable($userId)
    {
        $user = User::find($userId);
        if (!$user) {
            throw new \Exception('用户不存在');
        }

        // 不能禁用管理员
        if ($user->user_type == 1) {
            throw new \Exception('不能禁用管理员账号');
        }

        $user->status = 0;

        $result = $user->save();

        // 清除权限缓存
        Auth::clearCache($userId, TenantContext::getTenantId());

        return $result !== false;
    }

    /**
     * 分配角色
     *
     * @param int $userId
     * @param array $roleIds
     * @return void
     */
    protected function assignRoles($userId, array $roleIds)
    {
        // 删除旧的角色关联
        Db::table('ea_user_role')->where('user_id', $userId)->delete();

        // 插入新的角色关联
        if (!empty($roleIds)) {
            $data = [];
            foreach ($roleIds as $roleId) {
                $data[] = [
                    'user_id' => $userId,
                    'role_id' => $roleId,
                ];
            }

            Db::table('ea_user_role')->insertAll($data);
        }
    }

    /**
     * 导入用户
     *
     * @param array $users
     * @param TenantService|null $tenantService
     * @return array
     */
    public function import(array $users, TenantService $tenantService = null)
    {
        $success = 0;
        $fail = 0;
        $errors = [];

        foreach ($users as $index => $userData) {
            try {
                $this->create($userData, $tenantService);
                $success++;
            } catch (\Exception $e) {
                $fail++;
                $errors[] = "第" . ($index + 1) . "行: " . $e->getMessage();
            }
        }

        return [
            'success' => $success,
            'fail' => $fail,
            'errors' => $errors,
        ];
    }

    /**
     * 导出用户
     *
     * @param array $where
     * @param Auth|null $auth
     * @return array
     */
    public function export(array $where = [], Auth $auth = null)
    {
        $query = User::with(['dept', 'roles']);

        // 应用数据权限
        if ($auth) {
            $auth->applyDataScope($query, 'id', 'dept_id');
        }

        // 应用筛选条件
        if (!empty($where['username'])) {
            $query->where('username', 'like', '%' . $where['username'] . '%');
        }

        if (!empty($where['realname'])) {
            $query->where('realname', 'like', '%' . $where['realname'] . '%');
        }

        if (!empty($where['dept_id'])) {
            $query->where('dept_id', $where['dept_id']);
        }

        if (isset($where['status']) && $where['status'] !== '') {
            $query->where('status', $where['status']);
        }

        $list = $query->select()->toArray();

        // 格式化数据
        $exportData = [];
        foreach ($list as $user) {
            $exportData[] = [
                '用户名' => $user['username'],
                '姓名' => $user['realname'],
                '手机号' => $user['phone'],
                '邮箱' => $user['email'],
                '部门' => $user['dept']['dept_name'] ?? '',
                '角色' => implode(',', array_column($user['roles'] ?? [], 'role_name')),
                '状态' => $user['status'] == 1 ? '正常' : '禁用',
                '创建时间' => $user['create_time'],
            ];
        }

        return $exportData;
    }
}
