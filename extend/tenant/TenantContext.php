<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | 租户上下文管理类
// +----------------------------------------------------------------------

namespace tenant;

/**
 * 租户上下文类
 * 用于管理当前请求的租户信息
 */
class TenantContext
{
    /**
     * 当前租户ID
     * @var int|null
     */
    private static $tenantId = null;

    /**
     * 当前租户编码
     * @var string|null
     */
    private static $tenantCode = null;

    /**
     * 租户信息缓存
     * @var array|null
     */
    private static $tenantInfo = null;

    /**
     * 是否启用多租户
     * @var bool
     */
    private static $enabled = true;

    /**
     * 设置租户ID
     * @param int $tenantId
     */
    public static function setTenantId($tenantId)
    {
        self::$tenantId = $tenantId;
    }

    /**
     * 获取租户ID
     * @return int|null
     */
    public static function getTenantId()
    {
        return self::$tenantId;
    }

    /**
     * 设置租户编码
     * @param string $tenantCode
     */
    public static function setTenantCode($tenantCode)
    {
        self::$tenantCode = $tenantCode;
    }

    /**
     * 获取租户编码
     * @return string|null
     */
    public static function getTenantCode()
    {
        return self::$tenantCode;
    }

    /**
     * 设置租户信息
     * @param array $info
     */
    public static function setTenantInfo(array $info)
    {
        self::$tenantInfo = $info;
        if (isset($info['id'])) {
            self::$tenantId = $info['id'];
        }
        if (isset($info['tenant_code'])) {
            self::$tenantCode = $info['tenant_code'];
        }
    }

    /**
     * 获取租户信息
     * @return array|null
     */
    public static function getTenantInfo()
    {
        return self::$tenantInfo;
    }

    /**
     * 清除租户上下文
     */
    public static function clear()
    {
        self::$tenantId = null;
        self::$tenantCode = null;
        self::$tenantInfo = null;
    }

    /**
     * 检查是否已设置租户
     * @return bool
     */
    public static function hasTenant()
    {
        return self::$tenantId !== null;
    }

    /**
     * 启用多租户
     */
    public static function enable()
    {
        self::$enabled = true;
    }

    /**
     * 禁用多租户
     */
    public static function disable()
    {
        self::$enabled = false;
    }

    /**
     * 检查是否启用多租户
     * @return bool
     */
    public static function isEnabled()
    {
        return self::$enabled;
    }

    /**
     * 获取租户数据库名
     * @return string|null
     */
    public static function getTenantDatabase()
    {
        if (self::$tenantInfo && isset(self::$tenantInfo['db_name'])) {
            return self::$tenantInfo['db_name'];
        }

        // 如果没有独立数据库，使用租户编码生成
        if (self::$tenantCode) {
            return 'tenant_' . self::$tenantCode;
        }

        return null;
    }

    /**
     * 执行无租户上下文的回调
     * @param callable $callback
     * @return mixed
     */
    public static function withoutTenant(callable $callback)
    {
        $originalEnabled = self::$enabled;
        $originalTenantId = self::$tenantId;

        self::$enabled = false;

        try {
            return $callback();
        } finally {
            self::$enabled = $originalEnabled;
            self::$tenantId = $originalTenantId;
        }
    }

    /**
     * 使用指定租户执行回调
     * @param int $tenantId
     * @param callable $callback
     * @return mixed
     */
    public static function withTenant($tenantId, callable $callback)
    {
        $originalTenantId = self::$tenantId;
        self::$tenantId = $tenantId;

        try {
            return $callback();
        } finally {
            self::$tenantId = $originalTenantId;
        }
    }
}
