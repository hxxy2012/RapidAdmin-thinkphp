<?php
// +----------------------------------------------------------------------
// | 应用设置
// +----------------------------------------------------------------------

return [
    // 应用名称
    'app_name' => 'EnterprisePlus',

    // 应用地址
    'app_host' => env('app.host', ''),

    // 应用的命名空间
    'app_namespace' => 'app',

    // 是否启用路由
    'with_route' => true,

    // 默认应用
    'default_app' => 'admin',

    // 默认时区
    'default_timezone' => 'Asia/Shanghai',

    // 应用映射（可选）
    'app_map' => [],

    // 域名绑定（可选）
    'domain_bind' => [],

    // 禁止URL访问的应用列表（支持通配符）
    'deny_app_list' => [],

    // 异常页面的模板文件
    'exception_tmpl' => app()->getThinkPath() . 'tpl/think_exception.tpl',

    // 错误显示信息,非调试模式有效
    'error_message' => '页面错误！请稍后再试～',

    // 显示错误信息
    'show_error_msg' => true,

    // ============================================
    // JWT配置
    // ============================================

    // JWT密钥（生产环境必须修改为随机字符串）
    'jwt_secret' => env('app.jwt_secret', 'your-secret-key-change-this-in-production-' . md5(__FILE__)),

    // JWT过期时间（秒），默认2小时
    'jwt_expire' => env('app.jwt_expire', 7200),

    // JWT刷新宽限期（秒），默认30天
    'jwt_refresh_ttl' => env('app.jwt_refresh_ttl', 2592000),

    // ============================================
    // 安全配置
    // ============================================

    // 登录失败锁定次数
    'login_fail_limit' => env('app.login_fail_limit', 5),

    // 登录失败锁定时间（秒），默认30分钟
    'login_lock_time' => env('app.login_lock_time', 1800),

    // 密码默认过期天数，0表示永不过期
    'password_expire_days' => env('app.password_expire_days', 90),

    // 是否强制首次登录修改密码
    'force_change_password' => env('app.force_change_password', false),

    // ============================================
    // 分页配置
    // ============================================

    // 每页默认记录数
    'list_rows' => env('app.list_rows', 15),

    // 每页最大记录数
    'max_list_rows' => env('app.max_list_rows', 1000),

    // ============================================
    // 文件上传配置
    // ============================================

    // 文件上传大小限制（字节），默认10MB
    'upload_max_size' => env('app.upload_max_size', 10485760),

    // 允许上传的文件类型
    'upload_allowed_ext' => env('app.upload_allowed_ext', 'jpg,jpeg,png,gif,pdf,doc,docx,xls,xlsx,zip,rar'),

    // 文件上传目录
    'upload_path' => env('app.upload_path', runtime_path() . 'upload/'),

    // ============================================
    // 缓存配置
    // ============================================

    // 是否启用数据缓存
    'data_cache_enable' => env('app.data_cache_enable', true),

    // 缓存过期时间（秒），默认1小时
    'data_cache_expire' => env('app.data_cache_expire', 3600),
];
