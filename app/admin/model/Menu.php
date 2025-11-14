<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 菜单模型
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\common\model\BaseModel;

/**
 * 菜单模型
 */
class Menu extends BaseModel
{
    /**
     * 数据表名
     * @var string
     */
    protected $name = 'menu';

    /**
     * 菜单表是平台级别的（NULL为系统菜单，有tenant_id为租户自定义菜单）
     * @var bool
     */
    protected $isPlatformTable = false;

    /**
     * 允许tenant_id为NULL
     * @var bool
     */
    protected $multiTenant = false;

    /**
     * 菜单类型常量
     */
    const TYPE_DIR = 1;        // 目录
    const TYPE_MENU = 2;       // 菜单
    const TYPE_BUTTON = 3;     // 按钮

    /**
     * 字段类型转换
     * @var array
     */
    protected $type = [
        'id' => 'integer',
        'tenant_id' => 'integer',
        'parent_id' => 'integer',
        'menu_type' => 'integer',
        'sort' => 'integer',
        'visible' => 'integer',
        'status' => 'integer',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
    ];

    /**
     * 关联父菜单
     * @return \think\model\relation\BelongsTo
     */
    public function parent()
    {
        return $this->belongsTo(Menu::class, 'parent_id');
    }

    /**
     * 关联子菜单
     * @return \think\model\relation\HasMany
     */
    public function children()
    {
        return $this->hasMany(Menu::class, 'parent_id');
    }

    /**
     * 关联角色（多对多）
     * @return \think\model\relation\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_menu', 'role_id', 'menu_id');
    }

    /**
     * 菜单类型获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getMenuTypeTextAttr($value, $data)
    {
        $types = [
            self::TYPE_DIR => '目录',
            self::TYPE_MENU => '菜单',
            self::TYPE_BUTTON => '按钮',
        ];

        return $types[$data['menu_type']] ?? '未知';
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
     * 可见性获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getVisibleTextAttr($value, $data)
    {
        return $data['visible'] == 1 ? '显示' : '隐藏';
    }

    /**
     * 获取菜单树
     * @param int $parentId
     * @param int|null $tenantId
     * @return array
     */
    public static function getTree($parentId = 0, $tenantId = null)
    {
        $query = self::where('parent_id', $parentId)
            ->where('status', 1)
            ->order('sort', 'asc');

        // 如果指定租户ID，只获取该租户的菜单和系统菜单
        if ($tenantId !== null) {
            $query->where(function($q) use ($tenantId) {
                $q->whereNull('tenant_id')->whereOr('tenant_id', $tenantId);
            });
        } else {
            // 只获取系统菜单
            $query->whereNull('tenant_id');
        }

        $menus = $query->select();

        $tree = [];
        foreach ($menus as $menu) {
            $item = $menu->toArray();
            $item['children'] = self::getTree($menu->id, $tenantId);
            $tree[] = $item;
        }

        return $tree;
    }

    /**
     * 检查是否有子菜单
     * @return bool
     */
    public function hasChildren()
    {
        return $this->children()->count() > 0;
    }
}
