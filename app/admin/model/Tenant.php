<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 租户模型
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\common\model\BaseModel;

/**
 * 租户模型
 */
class Tenant extends BaseModel
{
    /**
     * 数据表名
     * @var string
     */
    protected $name = 'tenant';

    /**
     * 这是平台管理表，不需要租户过滤
     * @var bool
     */
    protected $isPlatformTable = true;

    /**
     * 状态常量
     */
    const STATUS_TRIAL = 0;      // 试用
    const STATUS_ACTIVE = 1;     // 正式
    const STATUS_EXPIRED = 2;    // 过期
    const STATUS_DISABLED = 3;   // 停用

    /**
     * 规模常量
     */
    const SCALE_1_50 = 1;        // 1-50人
    const SCALE_51_200 = 2;      // 51-200人
    const SCALE_201_500 = 3;     // 201-500人
    const SCALE_500_PLUS = 4;    // 500+人

    /**
     * JSON字段
     * @var array
     */
    protected $json = [];

    /**
     * 字段类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'package_id' => 'integer',
        'scale' => 'integer',
        'status' => 'integer',
        'max_users' => 'integer',
        'used_storage' => 'integer',
        'max_storage' => 'integer',
        'admin_user_id' => 'integer',
        'creator_id' => 'integer',
        'expire_time' => 'datetime',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'delete_time' => 'datetime',
    ];

    /**
     * 关联套餐
     * @return \think\model\relation\BelongsTo
     */
    public function package()
    {
        return $this->belongsTo(Package::class, 'package_id');
    }

    /**
     * 关联管理员用户
     * @return \think\model\relation\BelongsTo
     */
    public function adminUser()
    {
        return $this->belongsTo(User::class, 'admin_user_id');
    }

    /**
     * 状态获取器
     * @param $value
     * @return array
     */
    public function getStatusTextAttr($value, $data)
    {
        $status = [
            self::STATUS_TRIAL => '试用',
            self::STATUS_ACTIVE => '正式',
            self::STATUS_EXPIRED => '过期',
            self::STATUS_DISABLED => '停用',
        ];

        return $status[$data['status']] ?? '未知';
    }

    /**
     * 规模获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getScaleTextAttr($value, $data)
    {
        $scale = [
            self::SCALE_1_50 => '1-50人',
            self::SCALE_51_200 => '51-200人',
            self::SCALE_201_500 => '201-500人',
            self::SCALE_500_PLUS => '500+人',
        ];

        return $scale[$data['scale']] ?? '未知';
    }

    /**
     * 存储使用率获取器
     * @param $value
     * @param $data
     * @return float
     */
    public function getStorageUsageRateAttr($value, $data)
    {
        if ($data['max_storage'] == 0) {
            return 0;
        }

        return round(($data['used_storage'] / $data['max_storage']) * 100, 2);
    }

    /**
     * 是否过期获取器
     * @param $value
     * @param $data
     * @return bool
     */
    public function getIsExpiredAttr($value, $data)
    {
        if (empty($data['expire_time'])) {
            return false;
        }

        return strtotime($data['expire_time']) < time();
    }

    /**
     * 检查租户是否可用
     * @return bool
     */
    public function isAvailable()
    {
        // 状态必须是正式或试用
        if (!in_array($this->status, [self::STATUS_ACTIVE, self::STATUS_TRIAL])) {
            return false;
        }

        // 检查是否过期
        if ($this->expire_time && strtotime($this->expire_time) < time()) {
            return false;
        }

        return true;
    }

    /**
     * 根据租户编码获取租户
     * @param string $tenantCode
     * @return Tenant|null
     */
    public static function getByCode($tenantCode)
    {
        return self::where('tenant_code', $tenantCode)->find();
    }

    /**
     * 创建租户数据库
     * @return bool
     */
    public function createDatabase()
    {
        if (empty($this->db_name)) {
            return false;
        }

        try {
            // 使用mysql连接执行创建数据库命令（不能使用当前连接）
            $charset = \think\facade\Config::get('tenant.database_charset', 'utf8mb4');
            $collation = \think\facade\Config::get('tenant.database_collation', 'utf8mb4_unicode_ci');

            $sql = "CREATE DATABASE IF NOT EXISTS `{$this->db_name}` DEFAULT CHARACTER SET {$charset} COLLATE {$collation}";

            // 使用mysql连接执行
            \think\facade\Db::connect('mysql')->execute($sql);

            \think\facade\Log::info('Tenant database created', [
                'tenant_code' => $this->tenant_code,
                'database' => $this->db_name
            ]);

            return true;
        } catch (\Exception $e) {
            \think\facade\Log::error('Failed to create tenant database: ' . $e->getMessage(), [
                'tenant_code' => $this->tenant_code,
                'database' => $this->db_name
            ]);
            return false;
        }
    }

    /**
     * 初始化租户数据
     * @return bool
     */
    public function initTenantData()
    {
        // TODO: 复制系统表结构到租户数据库
        // TODO: 初始化默认数据（部门、角色、菜单等）
        return true;
    }

    /**
     * 更新存储使用量
     * @param int $size 字节数
     * @param bool $increase 是否增加（false为减少）
     * @return bool
     */
    public function updateStorageUsage($size, $increase = true)
    {
        if ($increase) {
            $this->used_storage += $size;
        } else {
            $this->used_storage -= $size;
            if ($this->used_storage < 0) {
                $this->used_storage = 0;
            }
        }

        return $this->save();
    }

    /**
     * 检查存储配额
     * @param int $size 要使用的大小（字节）
     * @return bool
     */
    public function checkStorageQuota($size)
    {
        return ($this->used_storage + $size) <= $this->max_storage;
    }
}
