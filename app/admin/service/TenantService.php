<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 租户业务服务层
// +----------------------------------------------------------------------

namespace app\admin\service;

use app\admin\model\Tenant;
use app\admin\model\Package;
use app\admin\model\User;
use app\admin\model\Role;
use think\facade\Db;
use think\facade\Log;
use think\facade\Cache;
use think\facade\Config;
use extend\tenant\TenantContext;

/**
 * 租户业务服务层
 */
class TenantService
{
    /**
     * 注册新租户
     *
     * @param array $data 租户注册数据
     * @return array
     * @throws \Exception
     */
    public function register(array $data)
    {
        // 开启事务
        Db::startTrans();

        try {
            // 1. 验证租户代码唯一性
            if (Tenant::where('tenant_code', $data['tenant_code'])->count() > 0) {
                throw new \Exception('租户代码已存在');
            }

            // 2. 验证套餐是否存在
            $package = Package::find($data['package_id']);
            if (!$package) {
                throw new \Exception('套餐不存在');
            }

            // 3. 计算过期时间
            $expireTime = $this->calculateExpireTime($package->package_cycle);

            // 4. 创建租户记录
            $tenant = new Tenant();
            $tenant->tenant_code = $data['tenant_code'];
            $tenant->tenant_name = $data['tenant_name'];
            $tenant->contact_name = $data['contact_name'];
            $tenant->contact_phone = $data['contact_phone'];
            $tenant->contact_email = $data['contact_email'] ?? '';
            $tenant->package_id = $data['package_id'];
            $tenant->db_name = 'tenant_' . $data['tenant_code'];
            $tenant->db_host = Config::get('database.connections.mysql.hostname');
            $tenant->db_port = Config::get('database.connections.mysql.hostport');
            $tenant->db_user = Config::get('database.connections.mysql.username');
            $tenant->db_password = Config::get('database.connections.mysql.password');
            $tenant->expire_time = $expireTime;
            $tenant->max_users = $package->max_users;
            $tenant->max_storage = $package->max_storage;
            $tenant->max_apps = $package->max_apps;
            $tenant->status = 1; // 正常
            $tenant->save();

            // 5. 创建租户数据库
            $this->createTenantDatabase($tenant);

            // 6. 初始化租户数据
            $this->initTenantDatabase($tenant, $package);

            // 7. 创建管理员账号
            $adminUser = $this->createAdminUser($tenant, [
                'username' => $data['admin_username'],
                'password' => $data['admin_password'],
                'realname' => $data['contact_name'],
                'phone' => $data['contact_phone'],
                'email' => $data['contact_email'] ?? '',
            ]);

            // 8. 记录日志
            Log::info('Tenant registered successfully', [
                'tenant_id' => $tenant->id,
                'tenant_code' => $tenant->tenant_code,
                'package_id' => $package->id,
                'admin_user_id' => $adminUser->id,
            ]);

            // 提交事务
            Db::commit();

            // 清除缓存
            $this->clearTenantCache($tenant->tenant_code);

            return [
                'tenant_id' => $tenant->id,
                'tenant_code' => $tenant->tenant_code,
                'admin_username' => $adminUser->username,
                'expire_time' => $tenant->expire_time,
            ];

        } catch (\Exception $e) {
            // 回滚事务
            Db::rollback();

            Log::error('Tenant registration failed: ' . $e->getMessage(), [
                'data' => $data,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            throw $e;
        }
    }

    /**
     * 创建租户数据库
     *
     * @param Tenant $tenant
     * @return void
     * @throws \Exception
     */
    protected function createTenantDatabase(Tenant $tenant)
    {
        try {
            $charset = Config::get('tenant.database_charset', 'utf8mb4');
            $collation = Config::get('tenant.database_collation', 'utf8mb4_unicode_ci');

            $sql = "CREATE DATABASE IF NOT EXISTS `{$tenant->db_name}`
                    DEFAULT CHARACTER SET {$charset} COLLATE {$collation}";

            // 使用mysql连接创建数据库
            Db::connect('mysql')->execute($sql);

            Log::info('Tenant database created', [
                'tenant_code' => $tenant->tenant_code,
                'database' => $tenant->db_name,
            ]);

        } catch (\Exception $e) {
            throw new \Exception('创建租户数据库失败: ' . $e->getMessage());
        }
    }

    /**
     * 初始化租户数据库（复制表结构和初始数据）
     *
     * @param Tenant $tenant
     * @param Package $package
     * @return void
     * @throws \Exception
     */
    protected function initTenantDatabase(Tenant $tenant, Package $package)
    {
        try {
            // 设置租户上下文
            TenantContext::setTenantId($tenant->id);
            TenantContext::setTenantCode($tenant->tenant_code);

            // 获取租户数据库连接
            $tenantDb = $this->getTenantConnection($tenant);

            // 需要初始化的表列表（只包含租户级别的表）
            $tables = [
                'ea_user',           // 用户表
                'ea_dept',           // 部门表
                'ea_role',           // 角色表
                'ea_menu',           // 菜单表（租户可自定义）
                'ea_permission',     // 权限表（租户可自定义）
                'ea_user_role',      // 用户角色关联
                'ea_role_menu',      // 角色菜单关联
                'ea_role_permission',// 角色权限关联
                'ea_role_dept',      // 角色部门关联（数据权限）
                'ea_dict_data',      // 字典数据
                'ea_config',         // 系统配置
                'ea_log_login',      // 登录日志
                'ea_log_operation',  // 操作日志
                'ea_log_error',      // 错误日志
                'ea_file',           // 文件表
                'ea_message',        // 消息表
                'ea_message_read',   // 消息读取记录
                'ea_flow_definition',// 流程定义
                'ea_flow_instance',  // 流程实例
                'ea_flow_task',      // 流程任务
                'ea_flow_history',   // 流程历史
                'ea_form_definition',// 表单定义
                'ea_form_data',      // 表单数据
                'ea_schedule_job',   // 定时任务
                'ea_schedule_log',   // 任务执行日志
            ];

            // 主库连接
            $mainDb = Db::connect('mysql');
            $prefix = Config::get('database.prefix', 'ea_');
            $mainDbName = Config::get('database.connections.mysql.database');

            // 复制表结构
            foreach ($tables as $table) {
                // 获取建表语句
                $createTableSql = $mainDb->query("SHOW CREATE TABLE `{$mainDbName}`.`{$table}`");
                if (empty($createTableSql)) {
                    Log::warning("Table {$table} not found in main database, skipping");
                    continue;
                }

                $createSql = $createTableSql[0]['Create Table'];

                // 执行建表
                $tenantDb->execute($createSql);

                Log::info("Table {$table} created in tenant database", [
                    'tenant_code' => $tenant->tenant_code,
                ]);
            }

            // 插入初始数据
            $this->insertInitialData($tenantDb, $tenant, $package);

        } catch (\Exception $e) {
            throw new \Exception('初始化租户数据库失败: ' . $e->getMessage());
        }
    }

    /**
     * 插入初始数据
     *
     * @param \think\db\ConnectionInterface $tenantDb
     * @param Tenant $tenant
     * @param Package $package
     * @return void
     */
    protected function insertInitialData($tenantDb, Tenant $tenant, Package $package)
    {
        $prefix = Config::get('database.prefix', 'ea_');
        $tenantId = $tenant->id;
        $now = date('Y-m-d H:i:s');

        // 1. 插入默认部门（公司总部）
        $tenantDb->execute("INSERT INTO {$prefix}dept
            (tenant_id, dept_name, dept_code, dept_type, parent_id, sort, status, create_time, update_time)
            VALUES
            (?, '总部', 'headquarters', 1, 0, 1, 1, ?, ?)",
            [$tenantId, $now, $now]
        );

        // 2. 插入系统角色（管理员、普通用户）
        $tenantDb->execute("INSERT INTO {$prefix}role
            (tenant_id, role_name, role_code, role_type, data_scope, sort, status, create_time, update_time)
            VALUES
            (?, '超级管理员', 'admin', 1, 1, 1, 1, ?, ?),
            (?, '普通用户', 'user', 1, 4, 2, 1, ?, ?)",
            [$tenantId, $now, $now, $tenantId, $now, $now]
        );

        // 3. 复制系统菜单（tenant_id为NULL的菜单）
        $mainDb = Db::connect('mysql');
        $mainDbName = Config::get('database.connections.mysql.database');

        $systemMenus = $mainDb->query("SELECT * FROM `{$mainDbName}`.`{$prefix}menu` WHERE tenant_id IS NULL ORDER BY id ASC");

        if (!empty($systemMenus)) {
            foreach ($systemMenus as $menu) {
                $tenantDb->execute("INSERT INTO {$prefix}menu
                    (tenant_id, parent_id, menu_name, menu_type, menu_code, route_path, component, icon, sort, visible, status, create_time, update_time)
                    VALUES
                    (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $menu['parent_id'],
                        $menu['menu_name'],
                        $menu['menu_type'],
                        $menu['menu_code'],
                        $menu['route_path'],
                        $menu['component'],
                        $menu['icon'],
                        $menu['sort'],
                        $menu['visible'],
                        $menu['status'],
                        $now,
                        $now,
                    ]
                );
            }
        }

        // 4. 复制系统权限（tenant_id为NULL的权限）
        $systemPermissions = $mainDb->query("SELECT * FROM `{$mainDbName}`.`{$prefix}permission` WHERE tenant_id IS NULL ORDER BY id ASC");

        if (!empty($systemPermissions)) {
            foreach ($systemPermissions as $permission) {
                $tenantDb->execute("INSERT INTO {$prefix}permission
                    (tenant_id, parent_id, permission_name, permission_code, permission_type, sort, status, create_time, update_time)
                    VALUES
                    (NULL, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $permission['parent_id'],
                        $permission['permission_name'],
                        $permission['permission_code'],
                        $permission['permission_type'],
                        $permission['sort'],
                        $permission['status'],
                        $now,
                        $now,
                    ]
                );
            }
        }

        // 5. 复制字典数据
        $dictData = $mainDb->query("SELECT * FROM `{$mainDbName}`.`{$prefix}dict_data` WHERE tenant_id IS NULL ORDER BY id ASC");

        if (!empty($dictData)) {
            foreach ($dictData as $dict) {
                $tenantDb->execute("INSERT INTO {$prefix}dict_data
                    (tenant_id, dict_type, dict_label, dict_value, dict_sort, status, remark, create_time, update_time)
                    VALUES
                    (NULL, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [
                        $dict['dict_type'],
                        $dict['dict_label'],
                        $dict['dict_value'],
                        $dict['dict_sort'],
                        $dict['status'],
                        $dict['remark'],
                        $now,
                        $now,
                    ]
                );
            }
        }

        // 6. 插入系统配置
        $tenantDb->execute("INSERT INTO {$prefix}config
            (tenant_id, config_group, config_key, config_value, config_type, remark, create_time, update_time)
            VALUES
            (?, 'system', 'site_name', ?, 'string', '站点名称', ?, ?),
            (?, 'system', 'site_logo', '', 'string', '站点Logo', ?, ?),
            (?, 'system', 'password_min_length', '6', 'number', '密码最小长度', ?, ?),
            (?, 'system', 'password_expire_days', '90', 'number', '密码过期天数', ?, ?),
            (?, 'system', 'login_fail_limit', '5', 'number', '登录失败限制次数', ?, ?),
            (?, 'system', 'login_fail_lock_time', '30', 'number', '登录失败锁定时间(分钟)', ?, ?)",
            [
                $tenantId, $tenant->tenant_name, $now, $now,
                $tenantId, $now, $now,
                $tenantId, $now, $now,
                $tenantId, $now, $now,
                $tenantId, $now, $now,
                $tenantId, $now, $now,
            ]
        );

        Log::info('Initial data inserted into tenant database', [
            'tenant_code' => $tenant->tenant_code,
        ]);
    }

    /**
     * 创建管理员账号
     *
     * @param Tenant $tenant
     * @param array $data
     * @return User
     * @throws \Exception
     */
    protected function createAdminUser(Tenant $tenant, array $data)
    {
        try {
            // 切换到租户数据库
            $tenantDb = $this->getTenantConnection($tenant);

            // 设置租户上下文
            TenantContext::setTenantId($tenant->id);
            TenantContext::setTenantCode($tenant->tenant_code);

            $prefix = Config::get('database.prefix', 'ea_');
            $now = date('Y-m-d H:i:s');

            // 获取默认部门ID
            $dept = $tenantDb->query("SELECT id FROM {$prefix}dept WHERE tenant_id = ? LIMIT 1", [$tenant->id]);
            $deptId = $dept[0]['id'] ?? 1;

            // 插入管理员用户
            $passwordHash = password_hash($data['password'], PASSWORD_BCRYPT);

            $tenantDb->execute("INSERT INTO {$prefix}user
                (tenant_id, dept_id, username, password, realname, phone, email, user_type, status, create_time, update_time)
                VALUES
                (?, ?, ?, ?, ?, ?, ?, 1, 1, ?, ?)",
                [
                    $tenant->id,
                    $deptId,
                    $data['username'],
                    $passwordHash,
                    $data['realname'],
                    $data['phone'],
                    $data['email'],
                    $now,
                    $now,
                ]
            );

            // 获取插入的用户ID
            $userId = $tenantDb->getLastInsID();

            // 获取管理员角色ID
            $role = $tenantDb->query("SELECT id FROM {$prefix}role WHERE tenant_id = ? AND role_code = 'admin' LIMIT 1", [$tenant->id]);
            $roleId = $role[0]['id'] ?? 1;

            // 关联管理员角色
            $tenantDb->execute("INSERT INTO {$prefix}user_role (user_id, role_id) VALUES (?, ?)", [$userId, $roleId]);

            Log::info('Admin user created for tenant', [
                'tenant_code' => $tenant->tenant_code,
                'username' => $data['username'],
                'user_id' => $userId,
            ]);

            // 返回用户对象
            $user = new User();
            $user->id = $userId;
            $user->username = $data['username'];
            $user->tenant_id = $tenant->id;

            return $user;

        } catch (\Exception $e) {
            throw new \Exception('创建管理员账号失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取租户数据库连接
     *
     * @param Tenant $tenant
     * @return \think\db\ConnectionInterface
     */
    protected function getTenantConnection(Tenant $tenant)
    {
        $config = Config::get('database.connections.mysql');
        $tenantConfig = array_merge($config, [
            'database' => $tenant->db_name,
            'prefix' => Config::get('database.prefix', 'ea_'),
        ]);

        Config::set(['connections' => ['tenant' => $tenantConfig]], 'database');

        return Db::connect('tenant');
    }

    /**
     * 计算过期时间
     *
     * @param string $cycle 套餐周期（month/quarter/year）
     * @return string
     */
    protected function calculateExpireTime($cycle)
    {
        $now = time();

        switch ($cycle) {
            case 'month':
                $expireTime = strtotime('+1 month', $now);
                break;
            case 'quarter':
                $expireTime = strtotime('+3 months', $now);
                break;
            case 'year':
                $expireTime = strtotime('+1 year', $now);
                break;
            case 'forever':
                $expireTime = strtotime('+100 years', $now);
                break;
            default:
                $expireTime = strtotime('+1 month', $now);
        }

        return date('Y-m-d H:i:s', $expireTime);
    }

    /**
     * 检查租户配额
     *
     * @param int $tenantId
     * @param string $type 配额类型（users/storage/apps）
     * @param int $increment 增量（默认1）
     * @return bool
     * @throws \Exception
     */
    public function checkQuota($tenantId, $type, $increment = 1)
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            throw new \Exception('租户不存在');
        }

        switch ($type) {
            case 'users':
                $current = $tenant->user_count ?? 0;
                $max = $tenant->max_users;
                break;
            case 'storage':
                $current = $tenant->storage_used ?? 0;
                $max = $tenant->max_storage;
                break;
            case 'apps':
                $current = $tenant->app_count ?? 0;
                $max = $tenant->max_apps;
                break;
            default:
                throw new \Exception('无效的配额类型');
        }

        // -1表示无限制
        if ($max == -1) {
            return true;
        }

        if (($current + $increment) > $max) {
            throw new \Exception("配额不足：当前{$type}使用量为{$current}，最大限制为{$max}");
        }

        return true;
    }

    /**
     * 更新租户配额使用量
     *
     * @param int $tenantId
     * @param string $type
     * @param int $increment
     * @return bool
     */
    public function updateQuota($tenantId, $type, $increment)
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return false;
        }

        switch ($type) {
            case 'users':
                $tenant->user_count = ($tenant->user_count ?? 0) + $increment;
                break;
            case 'storage':
                $tenant->storage_used = ($tenant->storage_used ?? 0) + $increment;
                break;
            case 'apps':
                $tenant->app_count = ($tenant->app_count ?? 0) + $increment;
                break;
        }

        return $tenant->save();
    }

    /**
     * 续费租户
     *
     * @param int $tenantId
     * @param int $packageId
     * @param array $paymentData
     * @return array
     * @throws \Exception
     */
    public function renewTenant($tenantId, $packageId, array $paymentData = [])
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            throw new \Exception('租户不存在');
        }

        $package = Package::find($packageId);
        if (!$package) {
            throw new \Exception('套餐不存在');
        }

        Db::startTrans();
        try {
            // 计算新的过期时间（从当前过期时间延长，如果已过期则从现在开始）
            $baseTime = strtotime($tenant->expire_time) > time()
                ? strtotime($tenant->expire_time)
                : time();

            switch ($package->package_cycle) {
                case 'month':
                    $newExpireTime = strtotime('+1 month', $baseTime);
                    break;
                case 'quarter':
                    $newExpireTime = strtotime('+3 months', $baseTime);
                    break;
                case 'year':
                    $newExpireTime = strtotime('+1 year', $baseTime);
                    break;
                case 'forever':
                    $newExpireTime = strtotime('+100 years', $baseTime);
                    break;
                default:
                    $newExpireTime = strtotime('+1 month', $baseTime);
            }

            // 更新租户信息
            $tenant->package_id = $packageId;
            $tenant->expire_time = date('Y-m-d H:i:s', $newExpireTime);
            $tenant->max_users = $package->max_users;
            $tenant->max_storage = $package->max_storage;
            $tenant->max_apps = $package->max_apps;

            // 如果之前是已过期状态，恢复为正常
            if ($tenant->status == 3) {
                $tenant->status = 1;
            }

            $tenant->save();

            // TODO: 记录订单和支付信息

            Db::commit();

            // 清除缓存
            $this->clearTenantCache($tenant->tenant_code);

            Log::info('Tenant renewed successfully', [
                'tenant_id' => $tenantId,
                'package_id' => $packageId,
                'new_expire_time' => $tenant->expire_time,
            ]);

            return [
                'tenant_id' => $tenant->id,
                'expire_time' => $tenant->expire_time,
                'package_name' => $package->package_name,
            ];

        } catch (\Exception $e) {
            Db::rollback();
            throw $e;
        }
    }

    /**
     * 禁用租户
     *
     * @param int $tenantId
     * @param string $reason
     * @return bool
     */
    public function disableTenant($tenantId, $reason = '')
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return false;
        }

        $tenant->status = 2; // 已停用
        $tenant->save();

        // 清除缓存
        $this->clearTenantCache($tenant->tenant_code);

        Log::info('Tenant disabled', [
            'tenant_id' => $tenantId,
            'reason' => $reason,
        ]);

        return true;
    }

    /**
     * 启用租户
     *
     * @param int $tenantId
     * @return bool
     */
    public function enableTenant($tenantId)
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            return false;
        }

        // 检查是否过期
        if (strtotime($tenant->expire_time) < time()) {
            throw new \Exception('租户已过期，请先续费');
        }

        $tenant->status = 1; // 正常
        $tenant->save();

        // 清除缓存
        $this->clearTenantCache($tenant->tenant_code);

        Log::info('Tenant enabled', [
            'tenant_id' => $tenantId,
        ]);

        return true;
    }

    /**
     * 清除租户缓存
     *
     * @param string $tenantCode
     * @return void
     */
    protected function clearTenantCache($tenantCode)
    {
        $cacheKey = Config::get('tenant.cache.prefix', 'tenant:') . $tenantCode;
        Cache::delete($cacheKey);
    }

    /**
     * 获取租户列表
     *
     * @param array $where
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function getList(array $where = [], $page = 1, $limit = 15)
    {
        $query = Tenant::with(['package']);

        // 租户代码
        if (!empty($where['tenant_code'])) {
            $query->where('tenant_code', 'like', '%' . $where['tenant_code'] . '%');
        }

        // 租户名称
        if (!empty($where['tenant_name'])) {
            $query->where('tenant_name', 'like', '%' . $where['tenant_name'] . '%');
        }

        // 状态
        if (isset($where['status']) && $where['status'] !== '') {
            $query->where('status', $where['status']);
        }

        // 套餐
        if (!empty($where['package_id'])) {
            $query->where('package_id', $where['package_id']);
        }

        // 即将过期（7天内）
        if (!empty($where['expiring_soon'])) {
            $query->where('expire_time', '<=', date('Y-m-d H:i:s', strtotime('+7 days')))
                  ->where('expire_time', '>', date('Y-m-d H:i:s'));
        }

        $total = $query->count();
        $list = $query->page($page, $limit)
                     ->order('id', 'desc')
                     ->select()
                     ->toArray();

        return [
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'list' => $list,
        ];
    }

    /**
     * 获取租户详情
     *
     * @param int $tenantId
     * @return array
     */
    public function getDetail($tenantId)
    {
        $tenant = Tenant::with(['package'])->find($tenantId);
        if (!$tenant) {
            throw new \Exception('租户不存在');
        }

        return $tenant->toArray();
    }

    /**
     * 更新租户信息
     *
     * @param int $tenantId
     * @param array $data
     * @return bool
     */
    public function update($tenantId, array $data)
    {
        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            throw new \Exception('租户不存在');
        }

        // 允许更新的字段
        $allowFields = [
            'tenant_name', 'contact_name', 'contact_phone', 'contact_email',
            'max_users', 'max_storage', 'max_apps', 'logo', 'remark'
        ];

        foreach ($allowFields as $field) {
            if (isset($data[$field])) {
                $tenant->$field = $data[$field];
            }
        }

        $result = $tenant->save();

        // 清除缓存
        $this->clearTenantCache($tenant->tenant_code);

        return $result !== false;
    }
}
