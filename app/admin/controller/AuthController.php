<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 认证控制器
// +----------------------------------------------------------------------

namespace app\admin\controller;

use app\admin\service\AuthService;
use app\common\controller\BaseController;
use think\Request;
use think\response\Json;

/**
 * 认证控制器
 */
class AuthController extends BaseController
{
    /**
     * @var AuthService
     */
    protected $authService;

    /**
     * 不需要登录的方法
     * @var array
     */
    protected $noLoginRequired = ['login', 'captcha'];

    /**
     * 构造函数
     */
    public function __construct()
    {
        $this->authService = new AuthService();
    }

    /**
     * 用户登录
     *
     * @param Request $request
     * @return Json
     */
    public function login(Request $request): Json
    {
        try {
            $username = $request->param('username');
            $password = $request->param('password');
            $tenantCode = $request->param('tenant_code');
            $captcha = $request->param('captcha'); // 验证码

            // 验证必填字段
            if (!$username || !$password || !$tenantCode) {
                return $this->error('用户名、密码和租户代码不能为空');
            }

            // TODO: 验证图形验证码
            // if (!$this->verifyCaptcha($captcha)) {
            //     return $this->error('验证码错误');
            // }

            // 执行登录
            $result = $this->authService->login($username, $password, $tenantCode);

            // 检查是否需要修改密码
            if (isset($result['require_password_change']) && $result['require_password_change']) {
                return $this->error($result['message'], [
                    'require_password_change' => true,
                    'user_id' => $result['user_id'],
                ], 403);
            }

            return $this->success('登录成功', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 退出登录
     *
     * @param Request $request
     * @return Json
     */
    public function logout(Request $request): Json
    {
        try {
            $token = $request->header('Authorization');

            if (!$token) {
                return $this->error('Token不能为空');
            }

            // 移除 "Bearer " 前缀
            $token = str_replace('Bearer ', '', $token);

            $this->authService->logout($token);

            return $this->success('退出成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 刷新Token
     *
     * @param Request $request
     * @return Json
     */
    public function refresh(Request $request): Json
    {
        try {
            $token = $request->header('Authorization');

            if (!$token) {
                return $this->error('Token不能为空');
            }

            // 移除 "Bearer " 前缀
            $token = str_replace('Bearer ', '', $token);

            $result = $this->authService->refreshToken($token);

            return $this->success('刷新成功', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 获取当前用户信息
     *
     * @param Request $request
     * @return Json
     */
    public function userInfo(Request $request): Json
    {
        try {
            // 从中间件注入的用户信息获取
            $userId = $this->user['id'] ?? null;
            $tenantId = $this->tenantId ?? null;

            if (!$userId || !$tenantId) {
                return $this->error('用户信息获取失败', null, 401);
            }

            $result = $this->authService->getUserInfo($userId, $tenantId);

            return $this->success('获取成功', $result);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 修改密码
     *
     * @param Request $request
     * @return Json
     */
    public function changePassword(Request $request): Json
    {
        try {
            $userId = $this->user['id'] ?? null;
            $tenantId = $this->tenantId ?? null;

            if (!$userId || !$tenantId) {
                return $this->error('用户信息获取失败', null, 401);
            }

            $oldPassword = $request->param('old_password');
            $newPassword = $request->param('new_password');
            $confirmPassword = $request->param('confirm_password');

            // 验证必填字段
            if (!$oldPassword || !$newPassword || !$confirmPassword) {
                return $this->error('所有字段都不能为空');
            }

            // 验证新密码长度
            if (strlen($newPassword) < 6) {
                return $this->error('新密码长度不能少于6位');
            }

            // 验证两次密码是否一致
            if ($newPassword !== $confirmPassword) {
                return $this->error('两次密码输入不一致');
            }

            // TODO: 实现修改密码逻辑
            // $userService->changePassword($userId, $oldPassword, $newPassword);

            return $this->success('密码修改成功');

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 生成图形验证码
     *
     * @return Json
     */
    public function captcha(): Json
    {
        try {
            // TODO: 实现图形验证码生成
            // 可以使用 think-captcha 扩展或自己实现

            $captcha = [
                'key' => uniqid(),
                'image' => 'data:image/png;base64,...', // Base64图片
            ];

            return $this->success('获取成功', $captcha);

        } catch (\Exception $e) {
            return $this->error($e->getMessage());
        }
    }

    /**
     * 验证图形验证码
     *
     * @param string $captcha
     * @return bool
     */
    protected function verifyCaptcha($captcha): bool
    {
        // TODO: 实现验证码验证逻辑
        return true;
    }
}
