<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Service;

use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboAppConfig;
use Tourze\SinaWeiboOAuth2Bundle\Exception\AuthorizationException;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboAppConfigRepository;

/**
 * OAuth2授权服务
 * 负责生成授权URL和处理授权相关逻辑
 */
class OAuth2AuthorizeService
{
    /**
     * 新浪微博OAuth2授权端点
     */
    private const AUTHORIZE_URL = 'https://api.weibo.com/oauth2/authorize';

    public function __construct(
        private readonly SinaWeiboAppConfigRepository $appConfigRepository
    ) {}

    /**
     * 生成授权URL
     *
     * @param string $appKey 应用Key
     * @param string|null $redirectUri 重定向地址（可选，使用配置中的默认地址）
     * @param string|null $scope 权限范围（可选）
     * @param string|null $state 状态参数（用于防CSRF攻击）
     * @param bool $forceLogin 是否强制登录
     * @throws AuthorizationException
     */
    public function generateAuthorizeUrl(
        string $appKey,
        ?string $redirectUri = null,
        ?string $scope = null,
        ?string $state = null,
        bool $forceLogin = false
    ): string {
        $appConfig = $this->getAppConfig($appKey);

        $params = [
            'client_id' => $appConfig->getAppKey(),
            'response_type' => 'code',
            'redirect_uri' => $redirectUri ?? $appConfig->getRedirectUri()
        ];

        // 添加可选参数
        if ($scope !== null) {
            $params['scope'] = $scope;
        } elseif ($appConfig->getScope()) {
            $params['scope'] = $appConfig->getScope();
        }

        if ($state !== null) {
            $params['state'] = $state;
        }

        if ($forceLogin) {
            $params['forcelogin'] = 'true';
        }

        return self::AUTHORIZE_URL . '?' . http_build_query($params);
    }

    /**
     * 生成随机状态参数（用于防CSRF攻击）
     */
    public function generateState(): string
    {
        return bin2hex(random_bytes(16));
    }

    /**
     * 验证授权回调参数
     *
     * @param array $params 回调参数
     * @param string|null $expectedState 期望的状态参数
     * @throws AuthorizationException
     */
    public function validateCallbackParams(array $params, ?string $expectedState = null): string
    {
        // 检查是否有错误参数
        if (isset($params['error'])) {
            $errorDescription = $params['error_description'] ?? null;
            throw new AuthorizationException($params['error'], $errorDescription);
        }

        // 检查是否有授权码
        if (!isset($params['code']) || empty($params['code'])) {
            throw new AuthorizationException(
                AuthorizationException::INVALID_REQUEST,
                '缺少授权码参数'
            );
        }

        // 验证状态参数（防CSRF攻击）
        if ($expectedState !== null) {
            $receivedState = $params['state'] ?? null;
            if ($receivedState !== $expectedState) {
                throw new AuthorizationException(
                    AuthorizationException::INVALID_REQUEST,
                    '状态参数不匹配，可能是CSRF攻击'
                );
            }
        }

        return $params['code'];
    }

    /**
     * 获取应用配置
     *
     * @throws AuthorizationException
     */
    private function getAppConfig(string $appKey): SinaWeiboAppConfig
    {
        $appConfig = $this->appConfigRepository->findByAppKey($appKey);

        if (!$appConfig) {
            throw new AuthorizationException(
                AuthorizationException::INVALID_CLIENT,
                '应用配置不存在'
            );
        }

        if (!$appConfig->isValid()) {
            throw new AuthorizationException(
                AuthorizationException::INVALID_CLIENT,
                '应用配置未激活'
            );
        }

        return $appConfig;
    }

    /**
     * 构建授权URL的辅助方法（带有所有默认参数）
     */
    public function buildDefaultAuthorizeUrl(string $appKey, ?string $state = null): string
    {
        $state = $state ?? $this->generateState();

        return $this->generateAuthorizeUrl(
            appKey: $appKey,
            state: $state,
            forceLogin: false
        );
    }

    /**
     * 解析授权回调并返回授权码
     * 这是一个便捷方法，结合了参数验证
     */
    public function handleCallback(array $callbackParams, ?string $expectedState = null): string
    {
        return $this->validateCallbackParams($callbackParams, $expectedState);
    }
}
