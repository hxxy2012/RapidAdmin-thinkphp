<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 基础模型类 - 支持多租户
// +----------------------------------------------------------------------

namespace app\common\model;

use think\Model;
use tenant\TenantContext;

/**
 * 基础模型类
 * 所有模型都应继承此类以支持多租户
 */
class BaseModel extends Model
{
    /**
     * 是否启用多租户自动过滤
     * @var bool
     */
    protected $multiTenant = true;

    /**
     * 租户字段名
     * @var string
     */
    protected $tenantField = 'tenant_id';

    /**
     * 是否为平台管理表（不需要租户过滤）
     * @var bool
     */
    protected $isPlatformTable = false;

    /**
     * 自动写入时间戳
     * @var bool
     */
    protected $autoWriteTimestamp = true;

    /**
     * 创建时间字段
     * @var string
     */
    protected $createTime = 'create_time';

    /**
     * 更新时间字段
     * @var string
     */
    protected $updateTime = 'update_time';

    /**
     * 软删除字段
     * @var string
     */
    protected $deleteTime = 'delete_time';

    /**
     * 模型初始化
     */
    protected static function init()
    {
        parent::init();

        // 查询时自动添加租户过滤
        static::event('before_select', function ($query) {
            static::addTenantScope($query);
        });

        static::event('before_find', function ($query) {
            static::addTenantScope($query);
        });

        // 写入时自动设置租户ID
        static::event('before_insert', function ($model) {
            static::setTenantId($model);
        });

        static::event('before_update', function ($model) {
            static::setTenantId($model);
        });
    }

    /**
     * 添加租户查询范围
     * @param \think\db\Query $query
     */
    protected static function addTenantScope($query)
    {
        $model = new static();

        // 如果不启用多租户，或者是平台表，则不添加租户过滤
        if (!$model->multiTenant || $model->isPlatformTable) {
            return;
        }

        // 如果租户上下文未启用，也不添加过滤
        if (!TenantContext::isEnabled()) {
            return;
        }

        $tenantId = TenantContext::getTenantId();

        // 只有存在租户ID时才添加过滤
        if ($tenantId !== null && in_array($model->tenantField, $model->getTableFields())) {
            $query->where($model->tenantField, $tenantId);
        }
    }

    /**
     * 设置租户ID
     * @param BaseModel $model
     */
    protected static function setTenantId($model)
    {
        // 如果不启用多租户，或者是平台表，则不设置租户ID
        if (!$model->multiTenant || $model->isPlatformTable) {
            return;
        }

        // 如果租户上下文未启用，也不设置
        if (!TenantContext::isEnabled()) {
            return;
        }

        $tenantId = TenantContext::getTenantId();

        // 只有存在租户ID且字段存在时才设置
        if ($tenantId !== null && in_array($model->tenantField, $model->getTableFields())) {
            // 如果模型中已经设置了租户ID，则不覆盖
            if (!isset($model->{$model->tenantField}) || $model->{$model->tenantField} === null) {
                $model->{$model->tenantField} = $tenantId;
            }
        }
    }

    /**
     * 不使用租户过滤查询
     * @return $this
     */
    public static function withoutTenant()
    {
        return (new static())->where(function ($query) {
            // 临时禁用租户过滤
            TenantContext::disable();
        });
    }

    /**
     * 使用全局租户查询
     * @return $this
     */
    public static function withGlobalTenant()
    {
        $instance = new static();
        $instance->isPlatformTable = true;
        return $instance;
    }

    /**
     * 获取表字段
     * @return array
     */
    protected function getTableFields()
    {
        static $fields = [];

        $table = $this->getTable();

        if (!isset($fields[$table])) {
            $fields[$table] = $this->db()->getTableFields($table);
        }

        return $fields[$table];
    }

    /**
     * JSON序列化配置
     * @var array
     */
    protected $json = [];

    /**
     * 类型转换配置
     * @var array
     */
    protected $type = [];
}
