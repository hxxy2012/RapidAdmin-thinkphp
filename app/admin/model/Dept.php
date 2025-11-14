<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 部门模型
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\common\model\BaseModel;

/**
 * 部门模型
 */
class Dept extends BaseModel
{
    /**
     * 数据表名
     * @var string
     */
    protected $name = 'dept';

    /**
     * 启用多租户
     * @var bool
     */
    protected $multiTenant = true;

    /**
     * 部门类型常量
     */
    const TYPE_COMPANY = 1;         // 公司
    const TYPE_SUB_COMPANY = 2;     // 分公司
    const TYPE_DIVISION = 3;        // 事业部
    const TYPE_DEPARTMENT = 4;      // 部门
    const TYPE_GROUP = 5;           // 小组

    /**
     * JSON字段
     * @var array
     */
    protected $json = ['vice_leader_ids'];

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
        'parent_id' => 'integer',
        'dept_type' => 'integer',
        'leader_id' => 'integer',
        'sort' => 'integer',
        'status' => 'integer',
        'creator_id' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'delete_time' => 'datetime',
    ];

    /**
     * 关联负责人
     * @return \think\model\relation\BelongsTo
     */
    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /**
     * 关联父部门
     * @return \think\model\relation\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Dept::class, 'parent_id');
    }

    /**
     * 关联子部门
     * @return \think\model\relation\HasMany
     */
    public function children()
    {
        return $this->hasMany(Dept::class, 'parent_id');
    }

    /**
     * 关联用户
     * @return \think\model\relation\HasMany
     */
    public function users()
    {
        return $this->hasMany(User::class, 'dept_id');
    }

    /**
     * 部门类型获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getDeptTypeTextAttr($value, $data)
    {
        $types = [
            self::TYPE_COMPANY => '公司',
            self::TYPE_SUB_COMPANY => '分公司',
            self::TYPE_DIVISION => '事业部',
            self::TYPE_DEPARTMENT => '部门',
            self::TYPE_GROUP => '小组',
        ];

        return $types[$data['dept_type']] ?? '未知';
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
     * 获取部门全路径名称
     * @return string
     */
    public function getFullNameAttr()
    {
        $names = [$this->dept_name];
        $parent = $this->parent;

        while ($parent) {
            array_unshift($names, $parent->dept_name);
            $parent = $parent->parent;
        }

        return implode(' / ', $names);
    }

    /**
     * 获取所有子部门ID（包括自己）
     * @return array
     */
    public function getChildrenIds()
    {
        $ids = [$this->id];

        $children = $this->children;
        foreach ($children as $child) {
            $ids = array_merge($ids, $child->getChildrenIds());
        }

        return $ids;
    }

    /**
     * 检查是否有子部门
     * @return bool
     */
    public function hasChildren()
    {
        return $this->children()->count() > 0;
    }
}
