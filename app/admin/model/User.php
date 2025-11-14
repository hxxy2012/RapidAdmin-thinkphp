<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 用户模型
// +----------------------------------------------------------------------

namespace app\admin\model;

use app\common\model\BaseModel;

/**
 * 用户模型
 */
class User extends BaseModel
{
    /**
     * 数据表名
     * @var string
     */
    protected $name = 'user';

    /**
     * 启用多租户
     * @var bool
     */
    protected $multiTenant = true;

    /**
     * 隐藏字段
     * @var array
     */
    protected $hidden = ['password'];

    /**
     * JSON字段
     * @var array
     */
    protected $json = ['post_ids', 'extra_fields'];

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
        'gender' => 'integer',
        'dept_id' => 'integer',
        'leader_id' => 'integer',
        'status' => 'integer',
        'user_type' => 'integer',
        'login_count' => 'integer',
        'pwd_expire_days' => 'integer',
        'creator_id' => 'integer',
        'birthday' => 'date',
        'entry_date' => 'date',
        'leave_date' => 'date',
        'login_time' => 'datetime',
        'pwd_update_time' => 'datetime',
        'create_time' => 'datetime',
        'update_time' => 'datetime',
        'delete_time' => 'datetime',
    ];

    /**
     * 性别常量
     */
    const GENDER_UNKNOWN = 0;
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;

    /**
     * 状态常量
     */
    const STATUS_DISABLED = 0;
    const STATUS_NORMAL = 1;

    /**
     * 用户类型常量
     */
    const TYPE_INTERNAL = 1;    // 内部用户
    const TYPE_EXTERNAL = 2;    // 外部用户
    const TYPE_PARTNER = 3;     // 合作伙伴

    /**
     * 关联部门
     * @return \think\model\relation\BelongsTo
     */
    public function dept()
    {
        return $this->belongsTo(Dept::class, 'dept_id');
    }

    /**
     * 关联直属上级
     * @return \think\model\relation\BelongsTo
     */
    public function leader()
    {
        return $this->belongsTo(User::class, 'leader_id');
    }

    /**
     * 关联角色（多对多）
     * @return \think\model\relation\BelongsToMany
     */
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_role', 'role_id', 'user_id');
    }

    /**
     * 密码设置器（自动加密）
     * @param $value
     * @return string
     */
    public function setPasswordAttr($value)
    {
        return password_hash($value, PASSWORD_DEFAULT);
    }

    /**
     * 性别获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getGenderTextAttr($value, $data)
    {
        $gender = [
            self::GENDER_UNKNOWN => '未知',
            self::GENDER_MALE => '男',
            self::GENDER_FEMALE => '女',
        ];

        return $gender[$data['gender']] ?? '未知';
    }

    /**
     * 状态获取器
     * @param $value
     * @param $data
     * @return string
     */
    public function getStatusTextAttr($value, $data)
    {
        return $data['status'] == self::STATUS_NORMAL ? '正常' : '禁用';
    }

    /**
     * 验证密码
     * @param string $password
     * @return bool
     */
    public function verifyPassword($password)
    {
        return password_verify($password, $this->password);
    }

    /**
     * 更新登录信息
     * @param string $ip 登录IP
     * @param string $userAgent 用户代理
     * @return bool
     */
    public function updateLoginInfo($ip, $userAgent = '')
    {
        $this->login_ip = $ip;
        $this->login_time = date('Y-m-d H:i:s');
        $this->login_count += 1;

        // 如果提供了user_agent，也更新
        if (!empty($userAgent)) {
            $this->user_agent = $userAgent;
        }

        return $this->save();
    }

    /**
     * 检查密码是否过期
     * @return bool
     */
    public function isPasswordExpired()
    {
        if (empty($this->pwd_update_time) || empty($this->pwd_expire_days)) {
            return false;
        }

        $expireTime = strtotime($this->pwd_update_time) + ($this->pwd_expire_days * 86400);

        return time() > $expireTime;
    }

    /**
     * 根据用户名获取用户
     * @param string $username
     * @param int|null $tenantId
     * @return User|null
     */
    public static function getByUsername($username, $tenantId = null)
    {
        $query = self::where('username', $username);

        if ($tenantId !== null) {
            $query->where('tenant_id', $tenantId);
        }

        return $query->find();
    }
}
