<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 租户管理控制器
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\admin\service\TenantService;
use app\common\controller\BaseController;
use think\Request;
use think\response\Json;

/**
 * 租户管理控制器
 */
class TenantController extends BaseController
{
    /**
     * @var TenantService
     */
    protected $tenantService;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->tenantService = new TenantService();
    }

    /**
     * 租户列表
     *
     * @param Request $request
     * @return Json
     */
    public function index(Request $request): Json
    {
        try {
            $where = [];

            // 租户代码
            if ($request->has('tenant_code')) {
                $where['tenant_code'] = $request->param('tenant_code');
            }

            // 租户名称
            if ($request->has('tenant_name')) {
                $where['tenant_name'] = $request->param('tenant_name');
            }

            // 状态
            if ($request->has('status')) {
                $where['status'] = $request->param('status');
            }

            // 套餐ID
            if ($request->has('package_id')) {
                $where['package_id'] = $request->param('package_id');
            }

            // 即将过期
            if ($request->has('expiring_soon')) {
                $where['expiring_soon'] = $request->param('expiring_soon');
            }

            $page = $request->param('page', 1);
            $limit = $request->param('limit', 15);

            $result = $this->tenantService->getList($where, $page, $limit);

            return $this->success('获取成功', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 租户详情
     *
     * @param Request $request
     * @return Json
     */
    public function read(Request $request): Json
    {
        try {
            $tenantId = $request->param('id');
            if (!$tenantId) {
                return $this->error('租户ID不能为空');
            }

            $result = $this->tenantService->getDetail($tenantId);

            return $this->success('获取成功', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 创建租户（注册）
     *
     * @param Request $request
     * @return Json
     */
    public function save(Request $request): Json
    {
        try {
            $data = $request->only([
                'tenant_code',
                'tenant_name',
                'contact_name',
                'contact_phone',
                'contact_email',
                'package_id',
                'admin_username',
                'admin_password',
            ]);

            // 验证必填字段
            $this->validateRequired($data, [
                'tenant_code' => '租户代码',
                'tenant_name' => '租户名称',
                'contact_name' => '联系人',
                'contact_phone' => '联系电话',
                'package_id' => '套餐',
                'admin_username' => '管理员账号',
                'admin_password' => '管理员密码',
            ]);

            // 验证租户代码格式（只允许字母、数字、下划线）
            if (!preg_match('/^[a-z0-9_]{3,20}$/', $data['tenant_code'])) {
                return $this->error('租户代码格式错误，只允许小写字母、数字、下划线，长度3-20位');
            }

            // 验证手机号
            if (!preg_match('/^1[3-9]\d{9}$/', $data['contact_phone'])) {
                return $this->error('联系电话格式错误');
            }

            // 验证邮箱（如果提供）
            if (!empty($data['contact_email']) && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
                return $this->error('邮箱格式错误');
            }

            // 验证密码长度
            if (strlen($data['admin_password']) < 6) {
                return $this->error('管理员密码长度不能少于6位');
            }

            $result = $this->tenantService->register($data);

            return $this->success('租户注册成功', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 更新租户信息
     *
     * @param Request $request
     * @return Json
     */
    public function update(Request $request): Json
    {
        try {
            $tenantId = $request->param('id');
            if (!$tenantId) {
                return $this->error('租户ID不能为空');
            }

            $data = $request->only([
                'tenant_name',
                'contact_name',
                'contact_phone',
                'contact_email',
                'max_users',
                'max_storage',
                'max_apps',
                'logo',
                'remark',
            ]);

            // 验证手机号
            if (!empty($data['contact_phone']) && !preg_match('/^1[3-9]\d{9}$/', $data['contact_phone'])) {
                return $this->error('联系电话格式错误');
            }

            // 验证邮箱
            if (!empty($data['contact_email']) && !filter_var($data['contact_email'], FILTER_VALIDATE_EMAIL)) {
                return $this->error('邮箱格式错误');
            }

            $result = $this->tenantService->update($tenantId, $data);

            return $this->success('更新成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 启用租户
     *
     * @param Request $request
     * @return Json
     */
    public function enable(Request $request): Json
    {
        try {
            $tenantId = $request->param('id');
            if (!$tenantId) {
                return $this->error('租户ID不能为空');
            }

            $this->tenantService->enableTenant($tenantId);

            return $this->success('启用成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 禁用租户
     *
     * @param Request $request
     * @return Json
     */
    public function disable(Request $request): Json
    {
        try {
            $tenantId = $request->param('id');
            if (!$tenantId) {
                return $this->error('租户ID不能为空');
            }

            $reason = $request->param('reason', '');

            $this->tenantService->disableTenant($tenantId, $reason);

            return $this->success('禁用成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 续费租户
     *
     * @param Request $request
     * @return Json
     */
    public function renew(Request $request): Json
    {
        try {
            $tenantId = $request->param('id');
            $packageId = $request->param('package_id');

            if (!$tenantId || !$packageId) {
                return $this->error('租户ID和套餐ID不能为空');
            }

            // TODO: 接收支付信息
            $paymentData = [];

            $result = $this->tenantService->renewTenant($tenantId, $packageId, $paymentData);

            return $this->success('续费成功', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 检查配额
     *
     * @param Request $request
     * @return Json
     */
    public function checkQuota(Request $request): Json
    {
        try {
            $tenantId = $request->param('tenant_id');
            $type = $request->param('type'); // users/storage/apps
            $increment = $request->param('increment', 1);

            if (!$tenantId || !$type) {
                return $this->error('参数不完整');
            }

            $result = $this->tenantService->checkQuota($tenantId, $type, $increment);

            return $this->success('配额检查通过', ['available' => $result]);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取统计信息
     *
     * @return Json
     */
    public function statistics(): Json
    {
        try {
            $stats = [
                'total' => \app\admin\model\Tenant::count(),
                'active' => \app\admin\model\Tenant::where('status', 1)->count(),
                'disabled' => \app\admin\model\Tenant::where('status', 2)->count(),
                'expired' => \app\admin\model\Tenant::where('status', 3)->count(),
                'expiring_soon' => \app\admin\model\Tenant::where('expire_time', '<=', date('Y-m-d H:i:s', strtotime('+7 days')))
                    ->where('expire_time', '>', date('Y-m-d H:i:s'))
                    ->where('status', 1)
                    ->count(),
            ];

            return $this->success('获取成功', $stats);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 验证必填字段
     *
     * @param array $data
     * @param array $rules
     * @return void
     * @throws \Exception
     */
    protected function validateRequired(array $data, array $rules)
    {
        foreach ($rules as $field => $label) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new \Exception("{$label}不能为空");
            }
        }
    }
}
