<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 中间件配置
// +----------------------------------------------------------------------

return [
    // 全局中间件（按执行顺序）
    'default' => [
        // 跨域请求支持
        \think\middleware\AllowCrossDomain::class,

        // Session初始化
        \think\middleware\SessionInit::class,

        // 多语言加载
        // \think\middleware\LoadLangPack::class,

        // 请求缓存
        // \think\middleware\CheckRequestCache::class,
    ],

    // 应用中间件（可在路由中使用）
    'alias' => [
        // 租户中间件 - 识别租户并切换数据库
        'tenant' => \app\admin\middleware\TenantMiddleware::class,

        // 认证中间件 - 验证JWT Token
        'auth' => \app\admin\middleware\AuthMiddleware::class,

        // 权限中间件 - 验证用户权限
        'permission' => \app\admin\middleware\PermissionMiddleware::class,
    ],

    // 中间件优先级（数字越小优先级越高）
    'priority' => [
        \app\admin\middleware\TenantMiddleware::class => 10,
        \app\admin\middleware\AuthMiddleware::class => 20,
        \app\admin\middleware\PermissionMiddleware::class => 30,
        \think\middleware\SessionInit::class => 40,
        \think\middleware\AllowCrossDomain::class => 50,
    ],
];
