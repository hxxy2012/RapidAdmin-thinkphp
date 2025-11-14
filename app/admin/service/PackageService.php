<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 套餐业务服务层
// +----------------------------------------------------------------------

namespace app\admin\service;

use app\admin\model\Package;
use think\facade\Log;

/**
 * 套餐业务服务层
 */
class PackageService
{
    /**
     * 获取套餐列表
     *
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getList(array $where = [], $page = 1, $limit = 15)
    {
        $query = Package::order('sort', 'asc');

        // 套餐名称
        if (!empty($where['package_name'])) {
            $query->where('package_name', 'like', '%' . $where['package_name'] . '%');
        }

        // 套餐类型
        if (!empty($where['package_type'])) {
            $query->where('package_type', $where['package_type']);
        }

        // 状态
        if (isset($where['status']) && $where['status'] !== '') {
            $query->where('status', $where['status']);
        }

        $total = $query->count();
        $list = $query->page($page, $limit)->select()->toArray();

        return [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'list' => $list,
        ];
    }

    /**
     * 获取所有可用套餐（用于前台展示）
     *
     * @return array
     */
    public function getAvailablePackages()
    {
        $list = Package::where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();

        return $list;
    }

    /**
     * 获取套餐详情
     *
     * @param int $packageId
     * @return array
     * @throws \Exception
     */
    public function getDetail($packageId)
    {
        $package = Package::find($packageId);
        if (!$package) {
            throw new \Exception('套餐不存在');
        }

        return $package->toArray();
    }

    /**
     * 创建套餐
     *
     * @param array $data
     * @return int
     * @throws \Exception
     */
    public function create(array $data)
    {
        try {
            // 检查套餐编码是否已存在
            if (Package::where('package_code', $data['package_code'])->count() > 0) {
                throw new \Exception('套餐编码已存在');
            }

            $package = new Package();
            $package->package_code = $data['package_code'];
            $package->package_name = $data['package_name'];
            $package->package_type = $data['package_type'];
            $package->package_cycle = $data['package_cycle'];
            $package->price = $data['price'];
            $package->original_price = $data['original_price'] ?? $data['price'];
            $package->max_users = $data['max_users'] ?? -1;
            $package->max_storage = $data['max_storage'] ?? -1;
            $package->max_apps = $data['max_apps'] ?? -1;
            $package->features = $data['features'] ?? null;
            $package->sort = $data['sort'] ?? 0;
            $package->status = $data['status'] ?? 1;
            $package->description = $data['description'] ?? '';
            $package->save();

            Log::info('Package created', [
                'package_id' => $package->id,
                'package_code' => $package->package_code,
            ]);

            return $package->id;

        } catch (\Exception $e) {
            Log::error('Package creation failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 更新套餐
     *
     * @param int $packageId
     * @param array $data
     * @return bool
     * @throws \Exception
     */
    public function update($packageId, array $data)
    {
        try {
            $package = Package::find($packageId);
            if (!$package) {
                throw new \Exception('套餐不存在');
            }

            // 不允许修改套餐编码
            $allowFields = [
                'package_name', 'package_type', 'package_cycle',
                'price', 'original_price',
                'max_users', 'max_storage', 'max_apps',
                'features', 'sort', 'status', 'description'
            ];

            foreach ($allowFields as $field) {
                if (isset($data[$field])) {
                    $package->$field = $data[$field];
                }
            }

            $result = $package->save();

            Log::info('Package updated', [
                'package_id' => $packageId,
            ]);

            return $result !== false;

        } catch (\Exception $e) {
            Log::error('Package update failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 删除套餐
     *
     * @param int $packageId
     * @return bool
     * @throws \Exception
     */
    public function delete($packageId)
    {
        try {
            $package = Package::find($packageId);
            if (!$package) {
                throw new \Exception('套餐不存在');
            }

            // 检查是否有租户在使用
            $tenantCount = \app\admin\model\Tenant::where('package_id', $packageId)->count();
            if ($tenantCount > 0) {
                throw new \Exception('该套餐正在被使用，无法删除');
            }

            $result = $package->delete();

            Log::info('Package deleted', [
                'package_id' => $packageId,
            ]);

            return $result !== false;

        } catch (\Exception $e) {
            Log::error('Package deletion failed: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * 启用套餐
     *
     * @param int $packageId
     * @return bool
     * @throws \Exception
     */
    public function enable($packageId)
    {
        $package = Package::find($packageId);
        if (!$package) {
            throw new \Exception('套餐不存在');
        }

        $package->status = 1;
        return $package->save() !== false;
    }

    /**
     * 禁用套餐
     *
     * @param int $packageId
     * @return bool
     * @throws \Exception
     */
    public function disable($packageId)
    {
        $package = Package::find($packageId);
        if (!$package) {
            throw new \Exception('套餐不存在');
        }

        $package->status = 0;
        return $package->save() !== false;
    }

    /**
     * 套餐对比
     *
     * @param array $packageIds
     * @return array
     */
    public function comparePackages(array $packageIds)
    {
        $packages = Package::whereIn('id', $packageIds)
            ->where('status', 1)
            ->select()
            ->toArray();

        // 提取所有功能点
        $allFeatures = [];
        foreach ($packages as $package) {
            if (!empty($package['features'])) {
                $features = is_string($package['features'])
                    ? json_decode($package['features'], true)
                    : $package['features'];

                if (is_array($features)) {
                    foreach ($features as $feature) {
                        if (isset($feature['name'])) {
                            $allFeatures[$feature['name']] = true;
                        }
                    }
                }
            }
        }

        $allFeatureNames = array_keys($allFeatures);

        // 构建对比数据
        $comparison = [
            'packages' => $packages,
            'features' => $allFeatureNames,
            'comparison_matrix' => [],
        ];

        foreach ($packages as $package) {
            $packageFeatures = [];
            if (!empty($package['features'])) {
                $features = is_string($package['features'])
                    ? json_decode($package['features'], true)
                    : $package['features'];

                if (is_array($features)) {
                    foreach ($features as $feature) {
                        if (isset($feature['name'])) {
                            $packageFeatures[$feature['name']] = $feature['enabled'] ?? true;
                        }
                    }
                }
            }

            $matrix = [];
            foreach ($allFeatureNames as $featureName) {
                $matrix[$featureName] = $packageFeatures[$featureName] ?? false;
            }

            $comparison['comparison_matrix'][$package['id']] = $matrix;
        }

        return $comparison;
    }

    /**
     * 获取推荐套餐
     *
     * @param int $userCount 用户数量
     * @param int $storageNeeded 存储需求（MB）
     * @return array|null
     */
    public function getRecommendedPackage($userCount, $storageNeeded)
    {
        $packages = Package::where('status', 1)
            ->order('price', 'asc')
            ->select();

        foreach ($packages as $package) {
            // 检查用户数限制
            if ($package->max_users != -1 && $userCount > $package->max_users) {
                continue;
            }

            // 检查存储限制
            if ($package->max_storage != -1 && $storageNeeded > $package->max_storage) {
                continue;
            }

            // 返回第一个满足条件的套餐（价格最低）
            return $package->toArray();
        }

        return null;
    }
}
