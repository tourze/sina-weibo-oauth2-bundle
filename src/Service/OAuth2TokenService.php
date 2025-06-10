<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboAppConfig;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Token;
use Tourze\SinaWeiboOAuth2Bundle\Event\OAuth2AuthorizeFailureEvent;
use Tourze\SinaWeiboOAuth2Bundle\Event\OAuth2AuthorizeSuccessEvent;
use Tourze\SinaWeiboOAuth2Bundle\Exception\ApiException;
use Tourze\SinaWeiboOAuth2Bundle\Exception\AuthorizationException;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboAppConfigRepository;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2TokenRepository;

/**
 * OAuth2令牌服务
 * 负责获取、刷新和管理访问令牌
 */
class OAuth2TokenService
{
    /**
     * 新浪微博OAuth2令牌端点
     */
    private const ACCESS_TOKEN_URL = 'https://api.weibo.com/oauth2/access_token';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SinaWeiboAppConfigRepository $appConfigRepository,
        private readonly SinaWeiboOAuth2TokenRepository $tokenRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger,
        private readonly EventDispatcherInterface $eventDispatcher
    ) {}

    /**
     * 通过授权码获取访问令牌
     */
    public function getAccessTokenByCode(
        string $appKey,
        string $code,
        ?string $redirectUri = null,
        ?\Symfony\Component\Security\Core\User\UserInterface $user = null
    ): SinaWeiboOAuth2Token {
        $appConfig = $this->getAppConfig($appKey);

        $params = [
            'client_id' => $appConfig->getAppKey(),
            'client_secret' => $appConfig->getAppSecret(),
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirectUri ?? $appConfig->getRedirectUri()
        ];

        try {
            $response = $this->makeTokenRequest($params);
            $token = $this->createOrUpdateToken($appConfig, $response, $user);

            $this->logger->info('OAuth2 access token obtained successfully', [
                'app_key' => $appKey,
                'weibo_uid' => $token->getWeiboUid(),
                'user' => $user?->getUserIdentifier()
            ]);

            // 派发授权成功事件
            $this->eventDispatcher->dispatch(
                new OAuth2AuthorizeSuccessEvent($token),
                OAuth2AuthorizeSuccessEvent::NAME
            );

            return $token;
        } catch (\Exception $e) {
            $this->logger->error('Failed to obtain OAuth2 access token', [
                'app_key' => $appKey,
                'error' => $e->getMessage(),
                'exception' => $e
            ]);

            // 派发授权失败事件
            $this->eventDispatcher->dispatch(
                new OAuth2AuthorizeFailureEvent($e, $appConfig, $code),
                OAuth2AuthorizeFailureEvent::NAME
            );

            throw $e;
        }
    }

    /**
     * 刷新访问令牌
     */
    public function refreshAccessToken(SinaWeiboOAuth2Token $token): SinaWeiboOAuth2Token
    {
        if (!$token->isRefreshTokenValid()) {
            throw new AuthorizationException(
                AuthorizationException::INVALID_GRANT,
                '刷新令牌无效或已过期'
            );
        }

        $appConfig = $token->getAppConfig();

        $params = [
            'client_id' => $appConfig->getAppKey(),
            'client_secret' => $appConfig->getAppSecret(),
            'grant_type' => 'refresh_token',
            'refresh_token' => $token->getRefreshToken()
        ];

        try {
            $response = $this->makeTokenRequest($params);

            // 更新现有令牌
            $token->setAccessToken($response['access_token'])
                ->setTokenType($response['token_type'] ?? 'Bearer');

            if (isset($response['expires_in'])) {
                $token->setExpiresIn((int)$response['expires_in']);
            }

            if (isset($response['refresh_token'])) {
                $token->setRefreshToken($response['refresh_token']);
            }

            if (isset($response['scope'])) {
                $token->setScope($response['scope']);
            }

            $this->entityManager->persist($token);
            $this->entityManager->flush();

            $this->logger->info('OAuth2 token refreshed successfully', [
                'token_id' => $token->getId(),
                'weibo_uid' => $token->getWeiboUid()
            ]);

            return $token;
        } catch (\Exception $e) {
            $this->logger->error('Failed to refresh OAuth2 token', [
                'token_id' => $token->getId(),
                'weibo_uid' => $token->getWeiboUid(),
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * 发起令牌请求
     */
    private function makeTokenRequest(array $params): array
    {
        try {
            $response = $this->httpClient->request('POST', self::ACCESS_TOKEN_URL, [
                'body' => $params,
                'headers' => [
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ]
            ]);

            $responseData = $response->toArray(false);

            if ($response->getStatusCode() !== 200 || isset($responseData['error'])) {
                throw ApiException::fromApiResponse($responseData);
            }

            return $responseData;
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                throw new ApiException('获取访问令牌失败: ' . $e->getMessage(), 0, null, $e);
            }
            throw $e;
        }
    }

    /**
     * 创建或更新令牌实体
     */
    private function createOrUpdateToken(
        SinaWeiboAppConfig $appConfig,
        array $response,
        ?\Symfony\Component\Security\Core\User\UserInterface $user = null
    ): SinaWeiboOAuth2Token {
        $weiboUid = (string)$response['uid'];

        // 查找现有令牌
        $token = $this->tokenRepository->findByAppConfigAndWeiboUid($appConfig, $weiboUid);

        if (!$token) {
            $token = new SinaWeiboOAuth2Token();
            $token->setAppConfig($appConfig)
                ->setWeiboUid($weiboUid);
        }

        // 更新令牌信息
        $token->setAccessToken($response['access_token'])
            ->setTokenType($response['token_type'] ?? 'Bearer')
            ->setValid(true);

        if ($user !== null) {
            $token->setUser($user);
        }

        if (isset($response['expires_in'])) {
            $token->setExpiresIn((int)$response['expires_in']);
        }

        if (isset($response['refresh_token'])) {
            $token->setRefreshToken($response['refresh_token']);
            $token->setRefreshExpiresTime(new \DateTime('+30 days'));
        }

        if (isset($response['scope'])) {
            $token->setScope($response['scope']);
        }

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        return $token;
    }

    /**
     * 获取应用配置
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
}
