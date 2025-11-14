<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 基础控制器
// +----------------------------------------------------------------------

namespace app\common\controller;

use think\App;
use think\response\Json;

/**
 * 基础控制器
 */
abstract class BaseController
{
    /**
     * Request实例
     * @var \think\Request
     */
    protected $request;

    /**
     * 应用实例
     * @var \think\App
     */
    protected $app;

    /**
     * 当前登录用户
     * @var array|null
     */
    protected $user;

    /**
     * 当前租户ID
     * @var int|null
     */
    protected $tenantId;

    /**
     * 构造方法
     * @param App $app
     */
    public function __construct(App $app)
    {
        $this->app = $app;
        $this->request = $this->app->request;

        // 控制器初始化
        $this->initialize();
    }

    /**
     * 初始化
     */
    protected function initialize()
    {
        // 获取当前登录用户（从中间件注入）
        $this->user = $this->request->user ?? null;
        $this->tenantId = $this->request->tenantId ?? null;
    }

    /**
     * 成功响应
     *
     * @param string $msg 提示信息
     * @param mixed $data 返回数据
     * @param int $code 状态码
     * @return Json
     */
    protected function success($msg = '操作成功', $data = null, $code = 200): Json
    {
        $result = [
            'code' => $code,
            'msg' => $msg,
        ];

        if ($data !== null) {
            $result['data'] = $data;
        }

        return json($result);
    }

    /**
     * 失败响应
     *
     * @param string $msg 错误信息
     * @param mixed $data 返回数据
     * @param int $code 状态码
     * @return Json
     */
    protected function error($msg = '操作失败', $data = null, $code = 400): Json
    {
        $result = [
            'code' => $code,
            'msg' => $msg,
        ];

        if ($data !== null) {
            $result['data'] = $data;
        }

        return json($result);
    }

    /**
     * 分页响应
     *
     * @param array $list 列表数据
     * @param int $total 总数
     * @param int $page 当前页
     * @param int $limit 每页数量
     * @return Json
     */
    protected function paginate($list, $total, $page = 1, $limit = 15): Json
    {
        return $this->success('获取成功', [
            'list' => $list,
            'total' => $total,
            'page' => $page,
            'limit' => $limit,
            'pages' => ceil($total / $limit),
        ]);
    }

    /**
     * 验证必填字段
     *
     * @param array $data 数据
     * @param array $rules 规则 ['field' => '字段名称']
     * @return void
     * @throws \Exception
     */
    protected function validateRequired(array $data, array $rules)
    {
        foreach ($rules as $field => $label) {
            if (!isset($data[$field]) || $data[$field] === '') {
                throw new \Exception("{$label}不能为空");
            }
        }
    }

    /**
     * 验证字段格式
     *
     * @param mixed $value 值
     * @param string $type 类型（mobile/email/id_card/url等）
     * @param string $label 字段名称
     * @return void
     * @throws \Exception
     */
    protected function validateFormat($value, $type, $label = '字段')
    {
        $patterns = [
            'mobile' => '/^1[3-9]\d{9}$/',
            'email' => '/^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/',
            'id_card' => '/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/',
            'url' => '/^https?:\/\/.+/',
            'username' => '/^[a-zA-Z0-9_]{4,20}$/',
            'password' => '/^.{6,20}$/',
        ];

        if (isset($patterns[$type])) {
            if (!preg_match($patterns[$type], $value)) {
                throw new \Exception("{$label}格式不正确");
            }
        } elseif ($type === 'email') {
            if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                throw new \Exception("{$label}格式不正确");
            }
        }
    }

    /**
     * 获取当前登录用户ID
     *
     * @return int
     * @throws \Exception
     */
    protected function getUserId(): int
    {
        if (!$this->user || !isset($this->user['id'])) {
            throw new \Exception('未登录或登录已过期');
        }

        return $this->user['id'];
    }

    /**
     * 获取当前租户ID
     *
     * @return int
     * @throws \Exception
     */
    protected function getTenantId(): int
    {
        if (!$this->tenantId) {
            throw new \Exception('租户信息未找到');
        }

        return $this->tenantId;
    }

    /**
     * 检查权限
     *
     * @param string $permission 权限标识
     * @return bool
     */
    protected function checkPermission($permission): bool
    {
        // TODO: 实现权限检查逻辑
        // 这里暂时返回true，后续在Auth类中实现
        return true;
    }
}
