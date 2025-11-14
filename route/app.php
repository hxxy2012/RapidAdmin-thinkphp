<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 路由配置文件
// +----------------------------------------------------------------------

use think\facade\Route;

// ============================================
// 公开API路由（无需认证）
// ============================================

// 租户注册
Route::post('tenant/register', 'app\admin\controller\TenantController@register');

// 用户登录/登出
Route::post('auth/login', 'app\admin\controller\AuthController@login');
Route::post('auth/logout', 'app\admin\controller\AuthController@logout')
    ->middleware(\app\admin\middleware\AuthMiddleware::class);

// Token刷新
Route::post('auth/refresh', 'app\admin\controller\AuthController@refreshToken');

// 获取当前用户信息
Route::get('auth/userinfo', 'app\admin\controller\AuthController@getUserInfo')
    ->middleware(\app\admin\middleware\AuthMiddleware::class);

// ============================================
// 需要认证的API路由
// ============================================
Route::group(function () {

    // ===== 租户管理 =====
    Route::group('tenant', function () {
        Route::get('list', 'app\admin\controller\TenantController@index');
        Route::get('detail/:id', 'app\admin\controller\TenantController@read');
        Route::put('update/:id', 'app\admin\controller\TenantController@update');
        Route::delete('delete/:id', 'app\admin\controller\TenantController@delete');
        Route::post('renew/:id', 'app\admin\controller\TenantController@renew');
    });

    // ===== 套餐管理 =====
    Route::group('package', function () {
        Route::get('list', 'app\admin\controller\PackageController@index');
        Route::post('create', 'app\admin\controller\PackageController@create');
        Route::get('detail/:id', 'app\admin\controller\PackageController@read');
        Route::put('update/:id', 'app\admin\controller\PackageController@update');
        Route::delete('delete/:id', 'app\admin\controller\PackageController@delete');
    });

    // ===== 用户管理 =====
    Route::group('user', function () {
        Route::get('list', 'app\admin\controller\UserController@index');
        Route::post('create', 'app\admin\controller\UserController@create');
        Route::get('detail/:id', 'app\admin\controller\UserController@read');
        Route::put('update/:id', 'app\admin\controller\UserController@update');
        Route::delete('delete/:id', 'app\admin\controller\UserController@delete');
        Route::post('reset-password/:id', 'app\admin\controller\UserController@resetPassword');
        Route::post('change-status/:id', 'app\admin\controller\UserController@changeStatus');
    });

    // ===== 角色管理 =====
    Route::group('role', function () {
        Route::get('list', 'app\admin\controller\RoleController@index');
        Route::post('create', 'app\admin\controller\RoleController@create');
        Route::get('detail/:id', 'app\admin\controller\RoleController@read');
        Route::put('update/:id', 'app\admin\controller\RoleController@update');
        Route::delete('delete/:id', 'app\admin\controller\RoleController@delete');
        Route::post('set-menus/:id', 'app\admin\controller\RoleController@setMenus');
        Route::post('set-permissions/:id', 'app\admin\controller\RoleController@setPermissions');
        Route::post('set-depts/:id', 'app\admin\controller\RoleController@setDepts');
    });

    // ===== 部门管理 =====
    Route::group('dept', function () {
        Route::get('tree', 'app\admin\controller\DeptController@tree');
        Route::get('list', 'app\admin\controller\DeptController@index');
        Route::post('create', 'app\admin\controller\DeptController@create');
        Route::get('detail/:id', 'app\admin\controller\DeptController@read');
        Route::put('update/:id', 'app\admin\controller\DeptController@update');
        Route::delete('delete/:id', 'app\admin\controller\DeptController@delete');
    });

    // ===== 菜单管理 =====
    Route::group('menu', function () {
        Route::get('tree', 'app\admin\controller\MenuController@tree');
        Route::get('list', 'app\admin\controller\MenuController@index');
        Route::post('create', 'app\admin\controller\MenuController@create');
        Route::get('detail/:id', 'app\admin\controller\MenuController@read');
        Route::put('update/:id', 'app\admin\controller\MenuController@update');
        Route::delete('delete/:id', 'app\admin\controller\MenuController@delete');
    });

    // ===== 权限管理 =====
    Route::group('permission', function () {
        Route::get('list', 'app\admin\controller\PermissionController@index');
        Route::post('create', 'app\admin\controller\PermissionController@create');
        Route::get('detail/:id', 'app\admin\controller\PermissionController@read');
        Route::put('update/:id', 'app\admin\controller\PermissionController@update');
        Route::delete('delete/:id', 'app\admin\controller\PermissionController@delete');
    });

    // ===== 表单设计器 =====
    Route::group('form', function () {
        Route::get('list', 'app\admin\controller\FormController@index');
        Route::post('create', 'app\admin\controller\FormController@create');
        Route::get('detail/:id', 'app\admin\controller\FormController@read');
        Route::put('update/:id', 'app\admin\controller\FormController@update');
        Route::delete('delete/:id', 'app\admin\controller\FormController@delete');
        Route::post('publish/:id', 'app\admin\controller\FormController@publish');
        Route::post('submit-data', 'app\admin\controller\FormController@submitData');
    });

    // ===== 流程定义 =====
    Route::group('flow', function () {
        Route::get('list', 'app\admin\controller\FlowController@index');
        Route::post('create', 'app\admin\controller\FlowController@create');
        Route::get('detail/:id', 'app\admin\controller\FlowController@read');
        Route::put('update/:id', 'app\admin\controller\FlowController@update');
        Route::delete('delete/:id', 'app\admin\controller\FlowController@delete');
        Route::post('publish/:id', 'app\admin\controller\FlowController@publish');
        Route::post('start', 'app\admin\controller\FlowController@startFlow');
    });

    // ===== 流程实例 =====
    Route::group('flow-instance', function () {
        Route::get('my-initiated', 'app\admin\controller\FlowInstanceController@myInitiated');
        Route::get('detail/:id', 'app\admin\controller\FlowInstanceController@read');
        Route::post('cancel/:id', 'app\admin\controller\FlowInstanceController@cancel');
        Route::get('history/:id', 'app\admin\controller\FlowInstanceController@getHistory');
    });

    // ===== 任务管理 =====
    Route::group('task', function () {
        Route::get('my-pending', 'app\admin\controller\TaskController@myPending');
        Route::get('my-completed', 'app\admin\controller\TaskController@myCompleted');
        Route::post('approve', 'app\admin\controller\TaskController@approve');
        Route::post('transfer/:id', 'app\admin\controller\TaskController@transfer');
    });

    // ===== 流程监控 =====
    Route::group('flow-monitor', function () {
        Route::get('statistics', 'app\admin\controller\FlowMonitorController@statistics');
        Route::get('instances', 'app\admin\controller\FlowMonitorController@instances');
        Route::get('tasks', 'app\admin\controller\FlowMonitorController@tasks');
    });

})->middleware([
    \app\admin\middleware\TenantMiddleware::class,
    \app\admin\middleware\AuthMiddleware::class,
]);
