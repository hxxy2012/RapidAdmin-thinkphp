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
     * @var string|false
     */
    protected $createTime = 'create_time';

    /**
     * 更新时间字段
     * @var string|false
     */
    protected $updateTime = 'update_time';

    /**
     * 软删除字段
     * @var string|false
     */
    protected $deleteTime = 'delete_time';

    /**
     * 是否跳过租户过滤（仅当前查询）
     * @var bool
     */
    protected $skipTenantFilter = false;

    /**
     * 查询前回调 - 自动添加租户过滤
     * @param \think\db\Query $query
     * @return void
     */
    public function onBeforeSelect($query)
    {
        $this->applyTenantScope($query);
    }

    /**
     * 查询单条前回调 - 自动添加租户过滤
     * @param \think\db\Query $query
     * @return void
     */
    public function onBeforeFind($query)
    {
        $this->applyTenantScope($query);
    }

    /**
     * 新增前回调 - 自动设置租户ID
     * @param Model $model
     * @return void
     */
    public function onBeforeInsert($model)
    {
        $this->fillTenantId($model);
    }

    /**
     * 更新前回调 - 自动设置租户ID
     * @param Model $model
     * @return void
     */
    public function onBeforeUpdate($model)
    {
        $this->fillTenantId($model);
    }

    /**
     * 应用租户查询范围
     * @param \think\db\Query $query
     * @return void
     */
    protected function applyTenantScope($query)
    {
        // 如果跳过租户过滤，直接返回
        if ($this->skipTenantFilter) {
            return;
        }

        // 如果不启用多租户，或者是平台表，则不添加租户过滤
        if (!$this->multiTenant || $this->isPlatformTable) {
            return;
        }

        // 如果租户上下文未启用，也不添加过滤
        if (!TenantContext::isEnabled()) {
            return;
        }

        $tenantId = TenantContext::getTenantId();

        // 只有存在租户ID时才添加过滤
        if ($tenantId !== null) {
            // 检查字段是否存在（避免报错）
            try {
                $fields = $this->getTableFields();
                if (in_array($this->tenantField, $fields)) {
                    $query->where($this->tenantField, $tenantId);
                }
            } catch (\Exception $e) {
                // 如果获取字段失败，尝试直接添加条件
                // 这样在表不存在时不会报错
                $query->where($this->tenantField, $tenantId);
            }
        }
    }

    /**
     * 填充租户ID
     * @param Model $model
     * @return void
     */
    protected function fillTenantId($model)
    {
        // 如果不启用多租户，或者是平台表，则不设置租户ID
        if (!$this->multiTenant || $this->isPlatformTable) {
            return;
        }

        // 如果租户上下文未启用，也不设置
        if (!TenantContext::isEnabled()) {
            return;
        }

        $tenantId = TenantContext::getTenantId();

        // 只有存在租户ID时才设置
        if ($tenantId !== null) {
            // 检查字段是否存在
            try {
                $fields = $this->getTableFields();
                if (in_array($this->tenantField, $fields)) {
                    // 如果模型中已经设置了租户ID，则不覆盖
                    if (!isset($model->{$this->tenantField}) || $model->{$this->tenantField} === null) {
                        $model->{$this->tenantField} = $tenantId;
                    }
                }
            } catch (\Exception $e) {
                // 忽略错误
            }
        }
    }

    /**
     * 获取表字段列表（带缓存）
     * @return array
     */
    protected function getTableFields()
    {
        static $fields = [];

        $table = $this->getTable();

        if (!isset($fields[$table])) {
            try {
                // ThinkPHP 8.x 正确的获取字段方法
                // 使用Db facade的getTableFields方法
                $tableFields = \think\facade\Db::getTableFields($table);
                $fields[$table] = $tableFields ?: [];
            } catch (\Exception $e) {
                // 如果获取失败，返回空数组
                $fields[$table] = [];
            }
        }

        return $fields[$table];
    }

    /**
     * 不使用租户过滤查询（仅当前查询）
     * @return static
     */
    public static function withoutTenant()
    {
        $model = new static();
        $model->skipTenantFilter = true;
        return $model;
    }

    /**
     * 使用全局租户查询
     * @return static
     */
    public static function withGlobalTenant()
    {
        $model = new static();
        $model->isPlatformTable = true;
        return $model;
    }

    /**
     * 执行无租户上下文的操作
     * @param callable $callback
     * @return mixed
     */
    public static function withoutTenantContext(callable $callback)
    {
        return TenantContext::withoutTenant($callback);
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

    /**
     * 只读字段（防止批量赋值）
     * @var array
     */
    protected $readonly = [];

    /**
     * 字段验证规则
     * @var array
     */
    protected $rule = [];

    /**
     * 获取器缓存
     * @var array
     */
    protected $withAttr = [];
}
