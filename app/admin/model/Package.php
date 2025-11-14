<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 套餐模型
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\common\model\BaseModel;

/**
 * 套餐模型
 */
class Package extends BaseModel
{
    /**
     * 数据表名
     * @var string
     */
    protected $name = 'package';

    /**
     * 这是平台管理表
     * @var bool
     */
    protected $isPlatformTable = true;

    /**
     * 套餐编码常量
     */
    const CODE_FREE = 'free';           // 免费版
    const CODE_BASIC = 'basic';         // 基础版
    const CODE_PRO = 'pro';             // 专业版
    const CODE_ENTERPRISE = 'enterprise'; // 企业版

    /**
     * JSON字段
     * @var array
     */
    protected $json = ['features'];

    /**
     * JSON字段格式化为数组
     * @var array
     */
    protected $jsonAssoc = true;

    /**
     * 字段类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'price_month' => 'float',
        'price_year' => 'float',
        'max_users' => 'integer',
        'max_storage' => 'integer',
        'max_apps' => 'integer',
        'max_workflows' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    /**
     * 状态获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusTextAttr($value, $data)
    {
        return $data['status'] == 1 ? '启用' : '停用';
    }

    /**
     * 存储容量格式化获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getMaxStorageTextAttr($value, $data)
    {
        return $this->formatBytes($data['max_storage']);
    }

    /**
     * 用户数格式化获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getMaxUsersTextAttr($value, $data)
    {
        return $data['max_users'] == -1 ? '不限' : $data['max_users'];
    }

    /**
     * 应用数格式化获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getMaxAppsTextAttr($value, $data)
    {
        return $data['max_apps'] == -1 ? '不限' : $data['max_apps'];
    }

    /**
     * 工作流数格式化获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getMaxWorkflowsTextAttr($value, $data)
    {
        return $data['max_workflows'] == -1 ? '不限' : $data['max_workflows'];
    }

    /**
     * 格式化字节数
     * @param int $bytes
     * @param int $precision
     * @return string
     */
    protected function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * 根据套餐编码获取套餐
     * @param string $code
     * @return Package|null
     */
    public static function getByCode($code)
    {
        return self::where('package_code', $code)
            ->where('status', 1)
            ->find();
    }

    /**
     * 获取所有启用的套餐
     * @return array
     */
    public static function getEnabled()
    {
        return self::where('status', 1)
            ->order('sort', 'asc')
            ->select()
            ->toArray();
    }

    /**
     * 检查功能是否可用
     * @param string $feature 功能标识
     * @return bool
     */
    public function hasFeature($feature)
    {
        if (empty($this->features)) {
            return false;
        }

        return isset($this->features[$feature]) && $this->features[$feature] === true;
    }

    /**
     * 获取功能值
     * @param string $feature
     * @param mixed $default
     * @return mixed
     */
    public function getFeature($feature, $default = null)
    {
        if (empty($this->features)) {
            return $default;
        }

        return $this->features[$feature] ?? $default;
    }

    /**
     * 关联租户
     * @return \think\model\relation\HasMany
     */
    public function tenants()
    {
        return $this->hasMany(Tenant::class, 'package_id');
    }
}
