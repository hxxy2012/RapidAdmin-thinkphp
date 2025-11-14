<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 套餐管理控制器
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\admin\service\PackageService;
use app\common\controller\BaseController;
use think\Request;
use think\response\Json;

/**
 * 套餐管理控制器
 */
class PackageController extends BaseController
{
    /**
     * @var PackageService
     */
    protected $packageService;

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->packageService = new PackageService();
    }

    /**
     * 套餐列表
     *
     * @param Request $request
     * @return Json
     */
    public function index(Request $request): Json
    {
        try {
            $where = [];

            // 套餐名称
            if ($request->has('package_name')) {
                $where['package_name'] = $request->param('package_name');
            }

            // 套餐类型
            if ($request->has('package_type')) {
                $where['package_type'] = $request->param('package_type');
            }

            // 状态
            if ($request->has('status')) {
                $where['status'] = $request->param('status');
            }

            $page = $request->param('page', 1);
            $limit = $request->param('limit', 15);

            $result = $this->packageService->getList($where, $page, $limit);

            return $this->success('获取成功', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取所有可用套餐（前台展示）
     *
     * @return Json
     */
    public function available(): Json
    {
        try {
            $result = $this->packageService->getAvailablePackages();

            return $this->success('获取成功', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 套餐详情
     *
     * @param Request $request
     * @return Json
     */
    public function read(Request $request): Json
    {
        try {
            $packageId = $request->param('id');
            if (!$packageId) {
                return $this->error('套餐ID不能为空');
            }

            $result = $this->packageService->getDetail($packageId);

            return $this->success('获取成功', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 创建套餐
     *
     * @param Request $request
     * @return Json
     */
    public function save(Request $request): Json
    {
        try {
            $data = $request->only([
                'package_code',
                'package_name',
                'package_type',
                'package_cycle',
                'price',
                'original_price',
                'max_users',
                'max_storage',
                'max_apps',
                'features',
                'sort',
                'status',
                'description',
            ]);

            // 验证必填字段
            $this->validateRequired($data, [
                'package_code' => '套餐编码',
                'package_name' => '套餐名称',
                'package_type' => '套餐类型',
                'package_cycle' => '套餐周期',
                'price' => '价格',
            ]);

            // 验证套餐编码格式
            if (!preg_match('/^[a-z0-9_]{3,20}$/', $data['package_code'])) {
                return $this->error('套餐编码格式错误，只允许小写字母、数字、下划线，长度3-20位');
            }

            // 验证价格
            if (!is_numeric($data['price']) || $data['price'] < 0) {
                return $this->error('价格格式错误');
            }

            // 处理features（如果是JSON字符串）
            if (!empty($data['features']) && is_string($data['features'])) {
                $features = json_decode($data['features'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->error('功能列表格式错误');
                }
                $data['features'] = $features;
            }

            $packageId = $this->packageService->create($data);

            return $this->success('创建成功', ['id' => $packageId]);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 更新套餐
     *
     * @param Request $request
     * @return Json
     */
    public function update(Request $request): Json
    {
        try {
            $packageId = $request->param('id');
            if (!$packageId) {
                return $this->error('套餐ID不能为空');
            }

            $data = $request->only([
                'package_name',
                'package_type',
                'package_cycle',
                'price',
                'original_price',
                'max_users',
                'max_storage',
                'max_apps',
                'features',
                'sort',
                'status',
                'description',
            ]);

            // 验证价格
            if (isset($data['price']) && (!is_numeric($data['price']) || $data['price'] < 0)) {
                return $this->error('价格格式错误');
            }

            // 处理features
            if (!empty($data['features']) && is_string($data['features'])) {
                $features = json_decode($data['features'], true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->error('功能列表格式错误');
                }
                $data['features'] = $features;
            }

            $this->packageService->update($packageId, $data);

            return $this->success('更新成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 删除套餐
     *
     * @param Request $request
     * @return Json
     */
    public function delete(Request $request): Json
    {
        try {
            $packageId = $request->param('id');
            if (!$packageId) {
                return $this->error('套餐ID不能为空');
            }

            $this->packageService->delete($packageId);

            return $this->success('删除成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 启用套餐
     *
     * @param Request $request
     * @return Json
     */
    public function enable(Request $request): Json
    {
        try {
            $packageId = $request->param('id');
            if (!$packageId) {
                return $this->error('套餐ID不能为空');
            }

            $this->packageService->enable($packageId);

            return $this->success('启用成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 禁用套餐
     *
     * @param Request $request
     * @return Json
     */
    public function disable(Request $request): Json
    {
        try {
            $packageId = $request->param('id');
            if (!$packageId) {
                return $this->error('套餐ID不能为空');
            }

            $this->packageService->disable($packageId);

            return $this->success('禁用成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 套餐对比
     *
     * @param Request $request
     * @return Json
     */
    public function compare(Request $request): Json
    {
        try {
            $packageIds = $request->param('package_ids');

            if (empty($packageIds)) {
                return $this->error('请选择要对比的套餐');
            }

            // 支持逗号分隔的字符串或数组
            if (is_string($packageIds)) {
                $packageIds = explode(',', $packageIds);
            }

            if (count($packageIds) < 2) {
                return $this->error('至少选择2个套餐进行对比');
            }

            if (count($packageIds) > 4) {
                return $this->error('最多只能对比4个套餐');
            }

            $result = $this->packageService->comparePackages($packageIds);

            return $this->success('获取成功', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 推荐套餐
     *
     * @param Request $request
     * @return Json
     */
    public function recommend(Request $request): Json
    {
        try {
            $userCount = $request->param('user_count', 10);
            $storageNeeded = $request->param('storage_needed', 1024); // MB

            $result = $this->packageService->getRecommendedPackage($userCount, $storageNeeded);

            if ($result) {
                return $this->success('获取成功', $result);
            } else {
                return $this->error('暂无符合条件的套餐');
            }

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }
}
