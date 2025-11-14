<?php
// +----------------------------------------------------------------------
// | 租户配置
// +----------------------------------------------------------------------

return [
    // 是否启用多租户
    'enabled' => true,

    // 是否使用独立数据库模式
    // true: 每个租户独立数据库
    // false: 共享数据库，通过tenant_id字段隔离
    'use_separate_database' => true,

    // 租户识别方式优先级
    // header: HTTP Header (X-Tenant-Code)
    // subdomain: 子域名
    // param: URL参数 (仅开发环境)
    // token: JWT Token
    // session: Session
    'resolve_priority' => ['header', 'subdomain', 'token', 'session', 'param'],

    // 租户数据库名前缀
    'database_prefix' => 'tenant_',

    // 租户数据库字符集
    'database_charset' => 'utf8mb4',

    // 租户数据库排序规则
    'database_collation' => 'utf8mb4_unicode_ci',

    // 系统表（不需要租户隔离的表）
    'system_tables' => [
        'tenant',
        'package',
        'tenant_package',
        'config',          // 系统配置
        'dict_type',       // 字典类型（系统级）
        'dict_data',       // 字典数据（系统级）
        'region',          // 地区表
        'plugin',          // 插件表
        'sensitive_word',  // 敏感词表
    ],

    // 租户默认配置
    'defaults' => [
        // 默认套餐
        'package_code' => 'free',

        // 默认试用期（天）
        'trial_days' => 30,

        // 默认最大用户数
        'max_users' => 10,

        // 默认最大存储（字节，默认1GB）
        'max_storage' => 1073741824,

        // 默认主题色
        'theme_color' => '#1890ff',

        // 默认系统名称
        'system_name' => 'EnterprisePlus',
    ],

    // 租户注册设置
    'register' => [
        // 是否允许自助注册
        'enabled' => true,

        // 注册需要邮箱验证
        'email_verify' => false,

        // 注册需要手机验证
        'phone_verify' => false,

        // 注册需要管理员审核
        'need_approval' => false,

        // 租户编码规则: auto(自动生成), custom(用户自定义)
        'code_rule' => 'auto',

        // 自动生成编码长度
        'code_length' => 8,
    ],

    // 租户初始化数据
    'init_data' => [
        // 是否初始化部门
        'init_dept' => true,

        // 默认部门名称
        'default_dept_name' => '总公司',

        // 是否初始化角色
        'init_role' => true,

        // 默认角色列表
        'default_roles' => [
            ['role_name' => '超级管理员', 'role_code' => 'admin', 'role_type' => 1],
            ['role_name' => '普通用户', 'role_code' => 'user', 'role_type' => 2],
        ],

        // 是否初始化菜单
        'init_menu' => true,
    ],

    // 租户配额限制
    'quota' => [
        // 检查用户配额
        'check_users' => true,

        // 检查存储配额
        'check_storage' => true,

        // 检查应用数量配额
        'check_apps' => true,

        // 检查工作流数量配额
        'check_workflows' => true,
    ],

    // 租户到期处理
    'expiration' => [
        // 到期前提醒天数
        'remind_days' => [30, 15, 7, 3, 1],

        // 到期后宽限期（天）
        'grace_period' => 7,

        // 到期后自动停用
        'auto_disable' => true,

        // 过期数据保留天数（之后可删除）
        'data_retention_days' => 90,
    ],

    // 租户缓存设置
    'cache' => [
        // 是否启用租户信息缓存
        'enabled' => true,

        // 缓存过期时间（秒）
        'expire' => 3600,

        // 缓存键前缀
        'prefix' => 'tenant:',
    ],
];
