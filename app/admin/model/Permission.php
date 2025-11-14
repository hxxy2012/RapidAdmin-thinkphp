<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 权限模型
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\common\model\BaseModel;

/**
 * 权限模型
 */
class Permission extends BaseModel
{
    /**
     * 数据表名
     * @var string
     */
    protected $name = 'permission';

    /**
     * 权限表是平台级别的（NULL为系统权限，有tenant_id为租户自定义权限）
     * @var bool
     */
    protected $isPlatformTable = false;

    /**
     * 允许tenant_id为NULL
     * @var bool
     */
    protected $multiTenant = false;

    /**
     * 权限类型常量
     */
    const TYPE_MENU = 1;       // 菜单权限
    const TYPE_BUTTON = 2;     // 按钮权限
    const TYPE_API = 3;        // 接口权限

    /**
     * 字段类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'permission_type' => 'integer',
        'parent_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    /**
     * 关联父权限
     * @return \think\model\relation\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Permission::class, 'parent_id');
    }

    /**
     * 关联子权限
     * @return \think\model\relation\HasMany
     */
    public function children()
    {
        return $this->hasMany(Permission::class, 'parent_id');
    }

    /**
     * 关联角色（多对多）
     * @return \think\model\relation\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_permission', 'role_id', 'permission_id');
    }

    /**
     * 权限类型获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getPermissionTypeTextAttr($value, $data)
    {
        $types = [
            self::TYPE_MENU => '菜单权限',
            self::TYPE_BUTTON => '按钮权限',
            self::TYPE_API => '接口权限',
        ];

        return $types[$data['permission_type']] ?? '未知';
    }

    /**
     * 状态获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusTextAttr($value, $data)
    {
        return $data['status'] == 1 ? '正常' : '停用';
    }

    /**
     * 根据权限标识获取权限
     * @param string $code
     * @param int|null $tenantId
     * @return Permission|null
     */
    public static function getByCode($code, $tenantId = null)
    {
        $query = self::where('permission_code', $code)
            ->where('status', 1);

        if ($tenantId !== null) {
            $query->where(function($q) use ($tenantId) {
                $q->whereNull('tenant_id')->whereOr('tenant_id', $tenantId);
            });
        } else {
            $query->whereNull('tenant_id');
        }

        return $query->find();
    }

    /**
     * 检查是否有子权限
     * @return bool
     */
    public function hasChildren()
    {
        return $this->children()->count() > 0;
    }
}
