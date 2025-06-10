<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Service;

use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Token;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboUserInfo;
use Tourze\SinaWeiboOAuth2Bundle\Event\OAuth2AuthorizeSuccessEvent;
use Tourze\SinaWeiboOAuth2Bundle\Exception\AuthorizationException;

/**
 * 新浪微博OAuth2门面服务
 * 统一提供所有OAuth2操作接口
 */
class SinaWeiboOAuth2Service
{
    public function __construct(
        private readonly OAuth2AuthorizeService $authorizeService,
        private readonly OAuth2TokenService $tokenService,
        private readonly SinaWeiboApiService $apiService,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * 生成授权URL
     */
    public function getAuthorizeUrl(
        string $appKey,
        ?string $redirectUri = null,
        ?string $scope = null,
        ?string $state = null,
        bool $forceLogin = false
    ): string {
        return $this->authorizeService->generateAuthorizeUrl(
            $appKey,
            $redirectUri,
            $scope,
            $state,
            $forceLogin
        );
    }

    /**
     * 生成默认授权URL（包含随机state参数）
     */
    public function getDefaultAuthorizeUrl(string $appKey): array
    {
        $state = $this->authorizeService->generateState();
        $url = $this->authorizeService->buildDefaultAuthorizeUrl($appKey, $state);

        return [
            'url' => $url,
            'state' => $state
        ];
    }

    /**
     * 处理授权回调获取访问令牌
     */
    public function handleCallback(
        string $appKey,
        array $callbackParams,
        ?string $expectedState = null,
        ?int $userId = null
    ): SinaWeiboOAuth2Token {
        $code = $this->authorizeService->handleCallback($callbackParams, $expectedState);

        return $this->tokenService->getAccessTokenByCode($appKey, $code, null, $userId);
    }

    /**
     * 完整的OAuth2授权流程
     * 返回授权URL，用户需要访问此URL进行授权
     */
    public function startAuthorization(string $appKey): array
    {
        return $this->getDefaultAuthorizeUrl($appKey);
    }

    /**
     * 完成OAuth2授权流程
     * 处理授权回调并获取用户信息
     */
    public function completeAuthorization(
        string $appKey,
        array $callbackParams,
        string $expectedState,
        ?int $userId = null
    ): array {
        // 获取访问令牌
        $token = $this->handleCallback($appKey, $callbackParams, $expectedState, $userId);

        // 获取用户信息
        $userInfo = $this->apiService->getUserInfo($token);

        // 派发包含用户信息的成功事件
        $this->eventDispatcher->dispatch(
            new OAuth2AuthorizeSuccessEvent($token, $userInfo, ['complete_flow' => true]),
            OAuth2AuthorizeSuccessEvent::NAME
        );

        return [
            'token' => $token,
            'user_info' => $userInfo
        ];
    }

    /**
     * 刷新访问令牌
     */
    public function refreshToken(SinaWeiboOAuth2Token $token): SinaWeiboOAuth2Token
    {
        return $this->tokenService->refreshAccessToken($token);
    }

    /**
     * 获取用户信息
     */
    public function getUserInfo(SinaWeiboOAuth2Token $token, ?string $uid = null): SinaWeiboUserInfo
    {
        return $this->apiService->getUserInfo($token, $uid);
    }

    /**
     * 验证令牌有效性
     */
    public function verifyToken(SinaWeiboOAuth2Token $token): bool
    {
        return $this->apiService->isTokenValid($token);
    }

    /**
     * 发送微博
     */
    public function postWeibo(SinaWeiboOAuth2Token $token, string $content): array
    {
        return $this->apiService->updateStatus($token, $content);
    }

    /**
     * 获取用户微博时间线
     */
    public function getUserTimeline(SinaWeiboOAuth2Token $token, ?string $uid = null, int $count = 20): array
    {
        return $this->apiService->getUserTimeline($token, $uid, $count);
    }

    /**
     * 获取用户粉丝列表
     */
    public function getFollowers(
        SinaWeiboOAuth2Token $token,
        ?string $uid = null,
        int $count = 20,
        int $cursor = 0
    ): array {
        return $this->apiService->getFollowers($token, $uid, $count, $cursor);
    }

    /**
     * 获取用户关注列表
     */
    public function getFriends(
        SinaWeiboOAuth2Token $token,
        ?string $uid = null,
        int $count = 20,
        int $cursor = 0
    ): array {
        return $this->apiService->getFriends($token, $uid, $count, $cursor);
    }

    /**
     * 获取公共微博时间线
     */
    public function getPublicTimeline(SinaWeiboOAuth2Token $token, int $count = 20): array
    {
        return $this->apiService->getPublicTimeline($token, $count);
    }

    /**
     * 便捷方法：检查并自动刷新令牌
     */
    public function ensureValidToken(SinaWeiboOAuth2Token $token): SinaWeiboOAuth2Token
    {
        if ($token->isValid()) {
            return $token;
        }

        if ($token->isRefreshTokenValid()) {
            return $this->refreshToken($token);
        }

        throw new AuthorizationException(
            AuthorizationException::INVALID_GRANT,
            '令牌已过期且无法刷新，需要重新授权'
        );
    }

    /**
     * 便捷方法：获取用户基本信息（自动处理令牌刷新）
     */
    public function getUserInfoSafely(SinaWeiboOAuth2Token $token): SinaWeiboUserInfo
    {
        $validToken = $this->ensureValidToken($token);
        return $this->getUserInfo($validToken);
    }
}
