<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 用户管理控制器
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\admin\service\UserService;
use app\admin\service\TenantService;
use app\common\controller\BaseController;
use think\Request;
use think\response\Json;

/**
 * 用户管理控制器
 */
class UserController extends BaseController
{
    /**
     * @var UserService
     */
    protected $userService;

    /**
     * @var TenantService
     */
    protected $tenantService;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->userService = new UserService();
        $this->tenantService = new TenantService();
    }

    /**
     * 用户列表
     *
     * @param Request $request
     * @return Json
     */
    public function index(Request $request): Json
    {
        try {
            $where = [];

            // 用户名
            if ($request->has('username')) {
                $where['username'] = $request->param('username');
            }

            // 真实姓名
            if ($request->has('realname')) {
                $where['realname'] = $request->param('realname');
            }

            // 手机号
            if ($request->has('phone')) {
                $where['phone'] = $request->param('phone');
            }

            // 部门ID
            if ($request->has('dept_id')) {
                $where['dept_id'] = $request->param('dept_id');
            }

            // 状态
            if ($request->has('status')) {
                $where['status'] = $request->param('status');
            }

            // 用户类型
            if ($request->has('user_type')) {
                $where['user_type'] = $request->param('user_type');
            }

            $page = $request->param('page', 1);
            $limit = $request->param('limit', 15);

            // 获取Auth实例（由PermissionMiddleware注入）
            $auth = $request->auth ?? null;

            $result = $this->userService->getList($where, $page, $limit, $auth);

            return $this->success('获取成功', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 用户详情
     *
     * @param Request $request
     * @return Json
     */
    public function read(Request $request): Json
    {
        try {
            $userId = $request->param('id');
            if (!$userId) {
                return $this->error('用户ID不能为空');
            }

            $result = $this->userService->getDetail($userId);

            return $this->success('获取成功', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 创建用户
     *
     * @param Request $request
     * @return Json
     */
    public function save(Request $request): Json
    {
        try {
            $data = $request->only([
                'username',
                'password',
                'realname',
                'phone',
                'email',
                'dept_id',
                'leader_id',
                'user_type',
                'gender',
                'avatar',
                'status',
                'remark',
                'role_ids',
            ]);

            // 验证必填字段
            $this->validateRequired($data, [
                'username' => '用户名',
                'password' => '密码',
                'realname' => '真实姓名',
            ]);

            // 验证用户名格式
            if (!preg_match('/^[a-zA-Z0-9_]{4,20}$/', $data['username'])) {
                return $this->error('用户名格式错误，只允许字母、数字、下划线，长度4-20位');
            }

            // 验证密码长度
            if (strlen($data['password']) < 6) {
                return $this->error('密码长度不能少于6位');
            }

            // 验证手机号
            if (!empty($data['phone'])) {
                $this->validateFormat($data['phone'], 'mobile', '手机号');
            }

            // 验证邮箱
            if (!empty($data['email'])) {
                $this->validateFormat($data['email'], 'email', '邮箱');
            }

            // 处理role_ids（支持JSON字符串或数组）
            if (isset($data['role_ids']) && is_string($data['role_ids'])) {
                $data['role_ids'] = json_decode($data['role_ids'], true);
            }

            $userId = $this->userService->create($data, $this->tenantService);

            return $this->success('创建成功', ['id' => $userId]);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 更新用户
     *
     * @param Request $request
     * @return Json
     */
    public function update(Request $request): Json
    {
        try {
            $userId = $request->param('id');
            if (!$userId) {
                return $this->error('用户ID不能为空');
            }

            $data = $request->only([
                'realname',
                'phone',
                'email',
                'dept_id',
                'leader_id',
                'gender',
                'avatar',
                'status',
                'remark',
                'role_ids',
            ]);

            // 验证手机号
            if (!empty($data['phone'])) {
                $this->validateFormat($data['phone'], 'mobile', '手机号');
            }

            // 验证邮箱
            if (!empty($data['email'])) {
                $this->validateFormat($data['email'], 'email', '邮箱');
            }

            // 处理role_ids
            if (isset($data['role_ids']) && is_string($data['role_ids'])) {
                $data['role_ids'] = json_decode($data['role_ids'], true);
            }

            $this->userService->update($userId, $data);

            return $this->success('更新成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除用户
     *
     * @param Request $request
     * @return Json
     */
    public function delete(Request $request): Json
    {
        try {
            $userId = $request->param('id');
            if (!$userId) {
                return $this->error('用户ID不能为空');
            }

            $this->userService->delete($userId, $this->tenantService);

            return $this->success('删除成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 重置密码
     *
     * @param Request $request
     * @return Json
     */
    public function resetPassword(Request $request): Json
    {
        try {
            $userId = $request->param('id');
            $newPassword = $request->param('password', '123456');

            if (!$userId) {
                return $this->error('用户ID不能为空');
            }

            $this->userService->resetPassword($userId, $newPassword);

            return $this->success('密码重置成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 启用用户
     *
     * @param Request $request
     * @return Json
     */
    public function enable(Request $request): Json
    {
        try {
            $userId = $request->param('id');
            if (!$userId) {
                return $this->error('用户ID不能为空');
            }

            $this->userService->enable($userId);

            return $this->success('启用成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 禁用用户
     *
     * @param Request $request
     * @return Json
     */
    public function disable(Request $request): Json
    {
        try {
            $userId = $request->param('id');
            if (!$userId) {
                return $this->error('用户ID不能为空');
            }

            $this->userService->disable($userId);

            return $this->success('禁用成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 导入用户
     *
     * @param Request $request
     * @return Json
     */
    public function import(Request $request): Json
    {
        try {
            // TODO: 处理上传的Excel文件
            // 这里假设已经解析好Excel数据
            $users = $request->param('users', []);

            if (empty($users)) {
                return $this->error('导入数据不能为空');
            }

            $result = $this->userService->import($users, $this->tenantService);

            return $this->success('导入完成', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 导出用户
     *
     * @param Request $request
     * @return Json
     */
    public function export(Request $request): Json
    {
        try {
            $where = [];

            if ($request->has('username')) {
                $where['username'] = $request->param('username');
            }

            if ($request->has('realname')) {
                $where['realname'] = $request->param('realname');
            }

            if ($request->has('dept_id')) {
                $where['dept_id'] = $request->param('dept_id');
            }

            if ($request->has('status')) {
                $where['status'] = $request->param('status');
            }

            $auth = $request->auth ?? null;

            $data = $this->userService->export($where, $auth);

            // TODO: 生成Excel文件
            // 这里返回数据，前端可以用js-xlsx等库生成Excel

            return $this->success('导出成功', $data);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
