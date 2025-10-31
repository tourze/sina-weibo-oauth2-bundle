<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2ApiException;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2ConfigurationException;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2Exception;
use Tourze\SinaWeiboOAuth2Bundle\Factory\SinaWeiboOAuth2UserFactory;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2ConfigRepository;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2StateRepository;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2UserRepository;
use Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service;

/**
 * OAuth2流程测试，使用Mock依赖
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2Service::class)]
#[RunTestsInSeparateProcesses]
final class OAuth2FlowTest extends AbstractIntegrationTestCase
{
    private SinaWeiboOAuth2Service $service;

    private SinaWeiboOAuth2ConfigRepository&MockObject $configRepository;

    private SinaWeiboOAuth2StateRepository&MockObject $stateRepository;

    private SinaWeiboOAuth2UserRepository&MockObject $userRepository;

    private SinaWeiboOAuth2UserFactory&MockObject $userFactory;

    private UrlGeneratorInterface&MockObject $urlGenerator;

    private HttpClientInterface&MockObject $httpClient;

    public function testGenerateAuthorizationUrlWithValidConfig(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_id');
        $config->setAppSecret('test_app_secret');
        $config->setScope('email');
        $config->setValid(true);

        $this->configRepository
            ->expects($this->once())
            ->method('findValidConfig')
            ->willReturn($config)
        ;

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->with('sina_weibo_oauth2_callback', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/callback')
        ;

        $url = $this->service->generateAuthorizationUrl('session123');

        $this->assertStringStartsWith('https://api.weibo.com/oauth2/authorize', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('client_id=test_app_id', $url);
        $this->assertStringContainsString('redirect_uri=https%3A%2F%2Fexample.com%2Fcallback', $url);
        $this->assertStringContainsString('scope=email', $url);
        $this->assertStringContainsString('state=', $url);
    }

    public function testGenerateAuthorizationUrlThrowsExceptionWhenNoConfig(): void
    {
        $this->configRepository
            ->expects($this->once())
            ->method('findValidConfig')
            ->willReturn(null)
        ;

        $this->expectException(SinaWeiboOAuth2ConfigurationException::class);
        $this->expectExceptionMessage('No valid Sina Weibo OAuth2 configuration found');

        $this->service->generateAuthorizationUrl();
    }

    public function testGenerateAuthorizationUrlWithDefaultScope(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_id');
        $config->setAppSecret('test_app_secret');
        $config->setValid(true);
        // No scope set, should use default

        $this->configRepository
            ->expects($this->once())
            ->method('findValidConfig')
            ->willReturn($config)
        ;

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('https://example.com/callback')
        ;

        $url = $this->service->generateAuthorizationUrl();

        $this->assertStringContainsString('scope=email', $url); // Default scope
    }

    public function testHandleCallbackWithValidStateAndCode(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_id');
        $config->setAppSecret('test_app_secret');
        $config->setValid(true);

        $state = new SinaWeiboOAuth2State();
        $state->setState('test_state');
        $state->setConfig($config);
        $state->setExpiresInMinutes(10);

        $user = new SinaWeiboOAuth2User();
        $user->setUid('123456');
        $user->setAccessToken('access_token');
        $user->setExpiresIn(7200);
        $user->setConfig($config);

        $this->stateRepository
            ->expects($this->once())
            ->method('findValidState')
            ->with('test_state')
            ->willReturn($state)
        ;

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('https://example.com/callback')
        ;

        // Mock token exchange response
        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('getStatusCode')->willReturn(200);
        $tokenResponse->method('getContent')->willReturn('{"access_token":"access_token","uid":"123456","expires_in":7200}');

        // Mock user info response
        $userInfoResponse = $this->createMock(ResponseInterface::class);
        $userInfoResponse->method('getStatusCode')->willReturn(200);
        $userInfoResponse->method('getContent')->willReturn('{"id":"123456","screen_name":"testuser","name":"Test User"}');

        $this->httpClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($tokenResponse, $userInfoResponse)
        ;

        $this->userRepository
            ->expects($this->once())
            ->method('findByUidAndConfig')
            ->with('123456', $config)
            ->willReturn(null)
        ;

        $this->userFactory
            ->expects($this->once())
            ->method('createFromData')
            ->willReturn($user)
        ;

        $result = $this->service->handleCallback('test_code', 'test_state');

        $this->assertInstanceOf(SinaWeiboOAuth2User::class, $result);
        $this->assertTrue($state->isUsed());
    }

    public function testHandleCallbackThrowsExceptionForInvalidState(): void
    {
        $this->stateRepository
            ->expects($this->once())
            ->method('findValidState')
            ->with('invalid_state')
            ->willReturn(null)
        ;

        $this->expectException(SinaWeiboOAuth2Exception::class);
        $this->expectExceptionMessage('Invalid or expired state');

        $this->service->handleCallback('test_code', 'invalid_state');
    }

    public function testHandleCallbackThrowsExceptionForExpiredState(): void
    {
        $config = new SinaWeiboOAuth2Config();
        // Simulate expired state by mocking isValid method
        $state = $this->createMock(SinaWeiboOAuth2State::class);
        $state->method('isValid')->willReturn(false);
        $state->method('getConfig')->willReturn($config);

        $this->stateRepository
            ->expects($this->once())
            ->method('findValidState')
            ->with('expired_state')
            ->willReturn($state)
        ;

        $this->expectException(SinaWeiboOAuth2Exception::class);
        $this->expectExceptionMessage('Invalid or expired state');

        $this->service->handleCallback('test_code', 'expired_state');
    }

    public function testHandleCallbackUpdatesExistingUser(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_id');
        $config->setAppSecret('test_app_secret');
        $config->setValid(true);

        $state = new SinaWeiboOAuth2State();
        $state->setState('test_state');
        $state->setConfig($config);
        $state->setExpiresInMinutes(10);

        $existingUser = new SinaWeiboOAuth2User();
        $existingUser->setUid('123456');
        $existingUser->setAccessToken('old_token');
        $existingUser->setExpiresIn(3600);
        $existingUser->setConfig($config);

        $updatedUser = new SinaWeiboOAuth2User();
        $updatedUser->setUid('123456');
        $updatedUser->setAccessToken('new_token');
        $updatedUser->setExpiresIn(7200);
        $updatedUser->setConfig($config);

        $this->stateRepository
            ->expects($this->once())
            ->method('findValidState')
            ->willReturn($state)
        ;

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('https://example.com/callback')
        ;

        // Mock responses
        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('getStatusCode')->willReturn(200);
        $tokenResponse->method('getContent')->willReturn('{"access_token":"new_token","uid":"123456","expires_in":7200}');

        $userInfoResponse = $this->createMock(ResponseInterface::class);
        $userInfoResponse->method('getStatusCode')->willReturn(200);
        $userInfoResponse->method('getContent')->willReturn('{"id":"123456","screen_name":"testuser"}');

        $this->httpClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($tokenResponse, $userInfoResponse)
        ;

        $this->userRepository
            ->expects($this->once())
            ->method('findByUidAndConfig')
            ->willReturn($existingUser)
        ;

        $this->userFactory
            ->expects($this->once())
            ->method('updateFromData')
            ->with($existingUser, self::isArray())
            ->willReturn($updatedUser)
        ;

        $result = $this->service->handleCallback('test_code', 'test_state');

        $this->assertSame($updatedUser, $result);
    }

    public function testGetUserInfoWithValidUser(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_id');
        $config->setAppSecret('test_app_secret');
        $config->setValid(true);

        $user = new SinaWeiboOAuth2User();
        $user->setUid('123456');
        $user->setAccessToken('access_token');
        $user->setExpiresIn(7200);
        $user->setConfig($config);
        $user->setRawData(['screen_name' => 'testuser', 'name' => 'Test User']);

        $this->userRepository
            ->expects($this->once())
            ->method('findByUid')
            ->with('123456')
            ->willReturn($user)
        ;

        $result = $this->service->getUserInfo('123456');

        $this->assertSame($user->getRawData(), $result);
    }

    public function testGetUserInfoThrowsExceptionForNonExistentUser(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findByUid')
            ->with('nonexistent')
            ->willReturn(null)
        ;

        $this->expectException(SinaWeiboOAuth2Exception::class);
        $this->expectExceptionMessage('User not found');

        $this->service->getUserInfo('nonexistent');
    }

    public function testGetUserInfoForcesRefresh(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_id');
        $config->setAppSecret('test_app_secret');
        $config->setValid(true);

        $user = new SinaWeiboOAuth2User();
        $user->setUid('123456');
        $user->setAccessToken('access_token');
        $user->setExpiresIn(7200);
        $user->setConfig($config);

        $this->userRepository
            ->expects($this->once())
            ->method('findByUid')
            ->willReturn($user)
        ;

        // Mock user info API response
        $userInfoResponse = $this->createMock(ResponseInterface::class);
        $userInfoResponse->method('getStatusCode')->willReturn(200);
        $userInfoResponse->method('getContent')->willReturn('{"id":"123456","screen_name":"updated_user","name":"Updated User"}');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($userInfoResponse)
        ;

        $result = $this->service->getUserInfo('123456', true); // Force refresh

        $this->assertIsArray($result);
        $this->assertArrayHasKey('id', $result);
        $this->assertSame('123456', $result['id']);
    }

    public function testRefreshTokenReturnsFalse(): void
    {
        // Sina Weibo doesn't support refresh tokens
        $result = $this->service->refreshToken('123456');

        $this->assertFalse($result);
    }

    public function testRefreshExpiredTokensReturnsZero(): void
    {
        $this->userRepository
            ->expects($this->once())
            ->method('findExpiredTokenUsers')
            ->willReturn([])
        ;

        $result = $this->service->refreshExpiredTokens();

        $this->assertSame(0, $result);
    }

    public function testCleanupExpiredStates(): void
    {
        $this->stateRepository
            ->expects($this->once())
            ->method('cleanupExpiredStates')
            ->willReturn(5)
        ;

        $result = $this->service->cleanupExpiredStates();

        $this->assertSame(5, $result);
    }

    public function testExchangeCodeForTokenThrowsApiExceptionOnHttpError(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_id');
        $config->setAppSecret('test_app_secret');
        $config->setValid(true);

        $state = new SinaWeiboOAuth2State();
        $state->setState('test_state');
        $state->setConfig($config);
        $state->setExpiresInMinutes(10);

        $this->stateRepository
            ->expects($this->once())
            ->method('findValidState')
            ->willReturn($state)
        ;

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('https://example.com/callback')
        ;

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willThrowException(new \Exception('Network error'))
        ;

        $this->expectException(SinaWeiboOAuth2ApiException::class);
        $this->expectExceptionMessage('Network error during token exchange');

        $this->service->handleCallback('test_code', 'test_state');
    }

    public function testExchangeCodeForTokenThrowsApiExceptionOnInvalidJson(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_id');
        $config->setAppSecret('test_app_secret');
        $config->setValid(true);

        $state = new SinaWeiboOAuth2State();
        $state->setState('test_state');
        $state->setConfig($config);
        $state->setExpiresInMinutes(10);

        $this->stateRepository
            ->expects($this->once())
            ->method('findValidState')
            ->willReturn($state)
        ;

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('https://example.com/callback')
        ;

        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('getStatusCode')->willReturn(200);
        $tokenResponse->method('getContent')->willReturn('invalid json');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($tokenResponse)
        ;

        $this->expectException(SinaWeiboOAuth2ApiException::class);
        $this->expectExceptionMessage('Failed to parse token response');

        $this->service->handleCallback('test_code', 'test_state');
    }

    public function testExchangeCodeForTokenThrowsApiExceptionOnApiError(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_id');
        $config->setAppSecret('test_app_secret');
        $config->setValid(true);

        $state = new SinaWeiboOAuth2State();
        $state->setState('test_state');
        $state->setConfig($config);
        $state->setExpiresInMinutes(10);

        $this->stateRepository
            ->expects($this->once())
            ->method('findValidState')
            ->willReturn($state)
        ;

        $this->urlGenerator
            ->expects($this->once())
            ->method('generate')
            ->willReturn('https://example.com/callback')
        ;

        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('getStatusCode')->willReturn(400);
        $tokenResponse->method('getContent')->willReturn('{"error":"invalid_grant","error_description":"Invalid authorization code"}');

        $this->httpClient
            ->expects($this->once())
            ->method('request')
            ->willReturn($tokenResponse)
        ;

        $this->expectException(SinaWeiboOAuth2ApiException::class);
        $this->expectExceptionMessage('Failed to exchange code for token: invalid_grant - Invalid authorization code');

        $this->service->handleCallback('test_code', 'test_state');
    }

    protected function onSetUp(): void
    {
        // Create mock objects
        $this->configRepository = $this->createMock(SinaWeiboOAuth2ConfigRepository::class);
        $this->stateRepository = $this->createMock(SinaWeiboOAuth2StateRepository::class);
        $this->userRepository = $this->createMock(SinaWeiboOAuth2UserRepository::class);
        $this->userFactory = $this->createMock(SinaWeiboOAuth2UserFactory::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->httpClient = $this->createMock(HttpClientInterface::class);

        // Create service with mocked dependencies
        // @phpstan-ignore-next-line 在集成测试类 Tourze\SinaWeiboOAuth2Bundle\Tests\Service\OAuth2FlowTest 中，不应直接实例化其测试目标 SinaWeiboOAuth2Service。
        $this->service = new SinaWeiboOAuth2Service(
            $this->httpClient,
            $this->configRepository,
            $this->stateRepository,
            $this->userRepository,
            $this->userFactory,
            $this->createMock(EntityManagerInterface::class),
            $this->urlGenerator,
            $this->createMock(LoggerInterface::class)
        );
    }
}
