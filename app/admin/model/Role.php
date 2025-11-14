<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 角色模型
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\common\model\BaseModel;

/**
 * 角色模型
 */
class Role extends BaseModel
{
    /**
     * 数据表名
     * @var string
     */
    protected $name = 'role';

    /**
     * 启用多租户
     * @var bool
     */
    protected $multiTenant = true;

    /**
     * 角色类型常量
     */
    const TYPE_SYSTEM = 1;      // 系统角色（不可删除）
    const TYPE_CUSTOM = 2;      // 自定义角色

    /**
     * 数据权限范围常量
     */
    const DATA_SCOPE_ALL = 1;               // 全部数据
    const DATA_SCOPE_DEPT = 2;              // 本部门数据
    const DATA_SCOPE_DEPT_AND_CHILD = 3;    // 本部门及子部门数据
    const DATA_SCOPE_SELF = 4;              // 仅本人数据
    const DATA_SCOPE_SELF_AND_SUB = 5;      // 本人及下属数据
    const DATA_SCOPE_CUSTOM_DEPT = 6;       // 自定义部门数据
    const DATA_SCOPE_CUSTOM_RULE = 7;       // 自定义规则

    /**
     * JSON字段
     * @var array
     */
    protected $json = ['dept_ids'];

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
        'tenant_id' => 'integer',
        'role_type' => 'integer',
        'data_scope' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'creator_id' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'delete_time' => 'datetime',
    ];

    /**
     * 关联用户（多对多）
     * @return \think\model\relation\BelongsToMany
     */
    public function users()
    {
        return $this->belongsToMany(User::class, 'user_role', 'user_id', 'role_id');
    }

    /**
     * 关联菜单（多对多）
     * @return \think\model\relation\BelongsToMany
     */
    public function menus()
    {
        return $this->belongsToMany(Menu::class, 'role_menu', 'menu_id', 'role_id');
    }

    /**
     * 关联权限（多对多）
     * @return \think\model\relation\BelongsToMany
     */
    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission', 'permission_id', 'role_id');
    }

    /**
     * 关联部门（多对多 - 数据权限）
     * @return \think\model\relation\BelongsToMany
     */
    public function depts()
    {
        return $this->belongsToMany(Dept::class, 'role_dept', 'dept_id', 'role_id');
    }

    /**
     * 角色类型获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getRoleTypeTextAttr($value, $data)
    {
        $types = [
            self::TYPE_SYSTEM => '系统角色',
            self::TYPE_CUSTOM => '自定义角色',
        ];

        return $types[$data['role_type']] ?? '未知';
    }

    /**
     * 数据权限范围获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getDataScopeTextAttr($value, $data)
    {
        $scopes = [
            self::DATA_SCOPE_ALL => '全部数据',
            self::DATA_SCOPE_DEPT => '本部门数据',
            self::DATA_SCOPE_DEPT_AND_CHILD => '本部门及子部门数据',
            self::DATA_SCOPE_SELF => '仅本人数据',
            self::DATA_SCOPE_SELF_AND_SUB => '本人及下属数据',
            self::DATA_SCOPE_CUSTOM_DEPT => '自定义部门数据',
            self::DATA_SCOPE_CUSTOM_RULE => '自定义规则',
        ];

        return $scopes[$data['data_scope']] ?? '未知';
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
     * 检查是否是系统角色
     * @return bool
     */
    public function isSystemRole()
    {
        return $this->role_type == self::TYPE_SYSTEM;
    }

    /**
     * 检查是否可删除
     * @return bool
     */
    public function canDelete()
    {
        // 系统角色不可删除
        if ($this->isSystemRole()) {
            return false;
        }

        // 有用户关联的角色不可删除
        if ($this->users()->count() > 0) {
            return false;
        }

        return true;
    }

    /**
     * 获取角色的菜单ID列表
     * @return array
     */
    public function getMenuIds()
    {
        return $this->menus()->column('id');
    }

    /**
     * 获取角色的权限编码列表
     * @return array
     */
    public function getPermissionCodes()
    {
        return $this->permissions()->column('permission_code');
    }
}
