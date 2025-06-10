<?php

namespace Tests\Tourze\SinaWeiboOAuth2Bundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboAppConfig;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Token;
use Tourze\SinaWeiboOAuth2Bundle\Event\OAuth2AuthorizeFailureEvent;
use Tourze\SinaWeiboOAuth2Bundle\Event\OAuth2AuthorizeSuccessEvent;
use Tourze\SinaWeiboOAuth2Bundle\Exception\ApiException;
use Tourze\SinaWeiboOAuth2Bundle\Exception\AuthorizationException;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboAppConfigRepository;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2TokenRepository;
use Tourze\SinaWeiboOAuth2Bundle\Service\OAuth2TokenService;

/**
 * OAuth2TokenService测试
 */
class OAuth2TokenServiceTest extends TestCase
{
    private OAuth2TokenService $service;
    private HttpClientInterface|MockObject $httpClient;
    private SinaWeiboAppConfigRepository|MockObject $appConfigRepository;
    private SinaWeiboOAuth2TokenRepository|MockObject $tokenRepository;
    private EntityManagerInterface|MockObject $entityManager;
    private LoggerInterface|MockObject $logger;
    private EventDispatcherInterface|MockObject $eventDispatcher;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->appConfigRepository = $this->createMock(SinaWeiboAppConfigRepository::class);
        $this->tokenRepository = $this->createMock(SinaWeiboOAuth2TokenRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->eventDispatcher = $this->createMock(EventDispatcherInterface::class);

        $this->service = new OAuth2TokenService(
            $this->httpClient,
            $this->appConfigRepository,
            $this->tokenRepository,
            $this->entityManager,
            $this->logger,
            $this->eventDispatcher
        );
    }

    public function testGetAccessTokenByCodeSuccess(): void
    {
        // 准备测试数据
        $appKey = 'test_app_key';
        $code = 'test_code';
        $appConfig = new SinaWeiboAppConfig();
        $appConfig->setAppKey($appKey)
            ->setAppSecret('test_secret')
            ->setRedirectUri('http://example.com/callback')
            ->setValid(true);

        $responseData = [
            'access_token' => 'test_access_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'test_refresh_token',
            'uid' => '123456789',
            'scope' => 'email'
        ];

        // 模拟依赖
        $this->appConfigRepository->expects($this->once())
            ->method('findByAppKey')
            ->with($appKey)
            ->willReturn($appConfig);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('POST', 'https://api.weibo.com/oauth2/access_token')
            ->willReturn($response);

        $this->tokenRepository->expects($this->once())
            ->method('findByAppConfigAndWeiboUid')
            ->willReturn(null);

        $this->entityManager->expects($this->once())
            ->method('persist');
        $this->entityManager->expects($this->once())
            ->method('flush');

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(OAuth2AuthorizeSuccessEvent::class),
                OAuth2AuthorizeSuccessEvent::NAME
            );

        // 执行测试
        $token = $this->service->getAccessTokenByCode($appKey, $code);

        // 验证结果
        $this->assertInstanceOf(SinaWeiboOAuth2Token::class, $token);
        $this->assertEquals('test_access_token', $token->getAccessToken());
        $this->assertEquals('Bearer', $token->getTokenType());
        $this->assertEquals('123456789', $token->getWeiboUid());
        $this->assertTrue($token->isValid());
    }

    public function testGetAccessTokenByCodeApiError(): void
    {
        // 准备测试数据
        $appKey = 'test_app_key';
        $code = 'invalid_code';
        $appConfig = new SinaWeiboAppConfig();
        $appConfig->setAppKey($appKey)
            ->setAppSecret('test_secret')
            ->setValid(true);

        $errorResponse = [
            'error' => 'invalid_grant',
            'error_description' => 'Invalid authorization code'
        ];

        // 模拟依赖
        $this->appConfigRepository->expects($this->once())
            ->method('findByAppKey')
            ->with($appKey)
            ->willReturn($appConfig);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(400);
        $response->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($errorResponse);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->eventDispatcher->expects($this->once())
            ->method('dispatch')
            ->with(
                $this->isInstanceOf(OAuth2AuthorizeFailureEvent::class),
                OAuth2AuthorizeFailureEvent::NAME
            );

        // 执行测试并验证异常
        $this->expectException(ApiException::class);
        $this->service->getAccessTokenByCode($appKey, $code);
    }

    public function testRefreshAccessTokenSuccess(): void
    {
        // 准备测试数据
        $appConfig = new SinaWeiboAppConfig();
        $appConfig->setAppKey('test_app_key')
            ->setAppSecret('test_secret')
            ->setValid(true);

        $token = new SinaWeiboOAuth2Token();
        $token->setAppConfig($appConfig)
            ->setRefreshToken('test_refresh_token')
            ->setRefreshExpiresTime(new \DateTime('+10 days'))
            ->setWeiboUid('123456789');

        $responseData = [
            'access_token' => 'new_access_token',
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'refresh_token' => 'new_refresh_token',
            'scope' => 'email'
        ];

        // 模拟依赖
        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($responseData);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($token);
        $this->entityManager->expects($this->once())
            ->method('flush');

        // 执行测试
        $refreshedToken = $this->service->refreshAccessToken($token);

        // 验证结果
        $this->assertSame($token, $refreshedToken);
        $this->assertEquals('new_access_token', $token->getAccessToken());
        $this->assertEquals('new_refresh_token', $token->getRefreshToken());
    }

    public function testRefreshAccessTokenInvalidRefreshToken(): void
    {
        // 准备测试数据
        $token = new SinaWeiboOAuth2Token();
        $token->setRefreshExpiresTime(new \DateTime('-1 day')); // 已过期

        // 执行测试并验证异常
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('刷新令牌无效或已过期');
        $this->service->refreshAccessToken($token);
    }

    public function testGetAppConfigNotFound(): void
    {
        // 模拟依赖
        $this->appConfigRepository->expects($this->once())
            ->method('findByAppKey')
            ->with('nonexistent_key')
            ->willReturn(null);

        // 执行测试并验证异常
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('应用配置不存在');
        $this->service->getAccessTokenByCode('nonexistent_key', 'test_code');
    }

    public function testGetAppConfigInactive(): void
    {
        // 准备测试数据
        $appConfig = new SinaWeiboAppConfig();
        $appConfig->setAppKey('test_key')
            ->setValid(false); // 未激活

        // 模拟依赖
        $this->appConfigRepository->expects($this->once())
            ->method('findByAppKey')
            ->willReturn($appConfig);

        // 执行测试并验证异常
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('应用配置未激活');
        $this->service->getAccessTokenByCode('test_key', 'test_code');
    }
}
