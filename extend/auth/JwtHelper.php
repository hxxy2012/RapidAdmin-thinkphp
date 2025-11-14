<?php
// +----------------------------------------------------------------------
// | EnterprisePlus [ 企业级SaaS开发平台 ]
// +----------------------------------------------------------------------
// | JWT令牌助手类
// +----------------------------------------------------------------------

namespace extend\auth;

use think\facade\Config;

/**
 * JWT令牌助手类
 *
 * 用于生成和验证JWT令牌
 */
class JwtHelper
{
    /**
     * 生成JWT Token
     *
     * @param array $payload 载荷数据
     * @param int $exp 过期时间（秒），默认7200秒（2小时）
     * @return string
     */
    public static function encode(array $payload, int $exp = 7200): string
    {
        // Header
        $header = [
            'typ' => 'JWT',
            'alg' => 'HS256'
        ];

        // Payload
        $now = time();
        $payload['iat'] = $now; // 签发时间
        $payload['exp'] = $now + $exp; // 过期时间
        $payload['nbf'] = $now; // 生效时间

        // Encode
        $headerEncoded = self::base64UrlEncode(json_encode($header));
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));

        // Signature
        $signature = self::signature($headerEncoded . '.' . $payloadEncoded);

        return $headerEncoded . '.' . $payloadEncoded . '.' . $signature;
    }

    /**
     * 解析JWT Token
     *
     * @param string $token
     * @return array|false
     */
    public static function decode(string $token)
    {
        $tokens = explode('.', $token);

        if (count($tokens) !== 3) {
            return false;
        }

        list($headerEncoded, $payloadEncoded, $signature) = $tokens;

        // 验证签名
        $expectedSignature = self::signature($headerEncoded . '.' . $payloadEncoded);
        if ($signature !== $expectedSignature) {
            return false;
        }

        // 解析payload
        $payload = json_decode(self::base64UrlDecode($payloadEncoded), true);

        if (!$payload) {
            return false;
        }

        // 验证时间
        $now = time();

        // 检查是否已过期
        if (isset($payload['exp']) && $payload['exp'] < $now) {
            return false;
        }

        // 检查是否已生效
        if (isset($payload['nbf']) && $payload['nbf'] > $now) {
            return false;
        }

        return $payload;
    }

    /**
     * 验证Token是否有效
     *
     * @param string $token
     * @return bool
     */
    public static function verify(string $token): bool
    {
        $payload = self::decode($token);
        return $payload !== false;
    }

    /**
     * 获取Token剩余有效时间（秒）
     *
     * @param string $token
     * @return int|false
     */
    public static function getTTL(string $token)
    {
        $payload = self::decode($token);

        if ($payload === false || !isset($payload['exp'])) {
            return false;
        }

        $ttl = $payload['exp'] - time();

        return $ttl > 0 ? $ttl : false;
    }

    /**
     * 刷新Token
     *
     * @param string $token
     * @param int $exp
     * @return string|false
     */
    public static function refresh(string $token, int $exp = 7200)
    {
        $payload = self::decode($token);

        if ($payload === false) {
            return false;
        }

        // 移除旧的时间戳
        unset($payload['iat'], $payload['exp'], $payload['nbf']);

        // 生成新token
        return self::encode($payload, $exp);
    }

    /**
     * 生成签名
     *
     * @param string $data
     * @return string
     */
    protected static function signature(string $data): string
    {
        $secret = Config::get('app.jwt_secret', 'your-secret-key-change-this-in-production');
        return self::base64UrlEncode(hash_hmac('sha256', $data, $secret, true));
    }

    /**
     * Base64 URL编码
     *
     * @param string $data
     * @return string
     */
    protected static function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    /**
     * Base64 URL解码
     *
     * @param string $data
     * @return string
     */
    protected static function base64UrlDecode(string $data): string
    {
        return base64_decode(strtr($data, '-_', '+/'));
    }

    /**
     * 从Token中提取用户ID
     *
     * @param string $token
     * @return int|false
     */
    public static function getUserId(string $token)
    {
        $payload = self::decode($token);

        if ($payload === false || !isset($payload['user_id'])) {
            return false;
        }

        return $payload['user_id'];
    }

    /**
     * 从Token中提取租户ID
     *
     * @param string $token
     * @return int|false
     */
    public static function getTenantId(string $token)
    {
        $payload = self::decode($token);

        if ($payload === false || !isset($payload['tenant_id'])) {
            return false;
        }

        return $payload['tenant_id'];
    }
}
