<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2ConfigurationException;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2Exception;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2ConfigRepository;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2StateRepository;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2UserRepository;
use Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service;

class SinaWeiboOAuth2ServiceTest extends TestCase
{
    private MockObject|HttpClientInterface $httpClient;
    private MockObject|SinaWeiboOAuth2ConfigRepository $configRepository;
    private MockObject|SinaWeiboOAuth2StateRepository $stateRepository;
    private MockObject|SinaWeiboOAuth2UserRepository $userRepository;
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|UrlGeneratorInterface $urlGenerator;
    private SinaWeiboOAuth2Service $service;

    public function testGenerateAuthorizationUrlSuccess(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_id')
            ->setAppSecret('test_secret')
            ->setScope('email');

        $this->configRepository->expects($this->once())
            ->method('findValidConfig')
            ->willReturn($config);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->with('sina_weibo_oauth2_callback', [], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/callback');

        $this->entityManager->expects($this->once())
            ->method('persist');
        $this->entityManager->expects($this->once())
            ->method('flush');

        $url = $this->service->generateAuthorizationUrl();

        $this->assertStringStartsWith('https://api.weibo.com/oauth2/authorize', $url);
        $this->assertStringContainsString('client_id=test_app_id', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('redirect_uri=', $url);
        $this->assertStringContainsString('state=', $url);
        $this->assertStringContainsString('scope=email', $url);
    }

    public function testGenerateAuthorizationUrlWithoutConfig(): void
    {
        $this->configRepository->expects($this->once())
            ->method('findValidConfig')
            ->willReturn(null);

        $this->expectException(SinaWeiboOAuth2ConfigurationException::class);
        $this->expectExceptionMessage('No valid Sina Weibo OAuth2 configuration found');

        $this->service->generateAuthorizationUrl();
    }

    public function testGenerateAuthorizationUrlWithSessionId(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_id')
            ->setAppSecret('test_secret');

        $this->configRepository->expects($this->once())
            ->method('findValidConfig')
            ->willReturn($config);

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('https://example.com/callback');

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($this->callback(function (SinaWeiboOAuth2State $state) {
                return $state->getSessionId() === 'test_session';
            }));
        $this->entityManager->expects($this->once())
            ->method('flush');

        $url = $this->service->generateAuthorizationUrl('test_session');

        $this->assertStringContainsString('scope=email', $url); // Default scope
    }

    public function testHandleCallbackSuccess(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_id')
            ->setAppSecret('test_secret');

        $state = new SinaWeiboOAuth2State('test_state', $config);

        $this->stateRepository->expects($this->once())
            ->method('findValidState')
            ->with('test_state')
            ->willReturn($state);

        $this->entityManager->expects($this->any())
            ->method('persist');
        $this->entityManager->expects($this->any())
            ->method('flush');

        $this->urlGenerator->expects($this->once())
            ->method('generate')
            ->willReturn('https://example.com/callback');

        // Mock token response
        $tokenResponse = $this->createMock(ResponseInterface::class);
        $tokenResponse->method('getContent')
            ->willReturn(json_encode([
                'access_token' => 'test_token',
                'expires_in' => 3600,
                'uid' => 'test_uid'
            ]));

        // Mock user info response
        $userInfoResponse = $this->createMock(ResponseInterface::class);
        $userInfoResponse->method('getContent')
            ->willReturn(json_encode([
                'id' => 'test_uid',
                'screen_name' => 'Test User',
                'profile_image_url' => 'http://example.com/avatar.jpg'
            ]));

        $this->httpClient->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($tokenResponse, $userInfoResponse);

        $user = new SinaWeiboOAuth2User('test_uid', 'test_token', 3600, $config);
        $this->userRepository->expects($this->once())
            ->method('updateOrCreate')
            ->willReturn($user);

        $result = $this->service->handleCallback('test_code', 'test_state');

        $this->assertInstanceOf(SinaWeiboOAuth2User::class, $result);
        $this->assertTrue($state->isUsed());
    }

    public function testHandleCallbackWithInvalidState(): void
    {
        $this->stateRepository->expects($this->once())
            ->method('findValidState')
            ->with('invalid_state')
            ->willReturn(null);

        $this->expectException(SinaWeiboOAuth2Exception::class);
        $this->expectExceptionMessage('Invalid or expired state');

        $this->service->handleCallback('test_code', 'invalid_state');
    }

    public function testGetUserInfoWithNonExistentUser(): void
    {
        $this->userRepository->expects($this->once())
            ->method('findByUid')
            ->with('non_existent_uid')
            ->willReturn(null);

        $this->expectException(SinaWeiboOAuth2Exception::class);
        $this->expectExceptionMessage('User not found');

        $this->service->getUserInfo('non_existent_uid');
    }

    public function testGetUserInfoWithValidTokenAndCachedData(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_id');

        $user = new SinaWeiboOAuth2User('test_uid', 'test_token', 3600, $config);
        $rawData = ['cached' => 'data'];
        $user->setRawData($rawData);

        $this->userRepository->expects($this->once())
            ->method('findByUid')
            ->with('test_uid')
            ->willReturn($user);

        $result = $this->service->getUserInfo('test_uid');

        $this->assertEquals($rawData, $result);
    }

    public function testRefreshExpiredTokensReturnsZero(): void
    {
        // Sina Weibo doesn't support refresh tokens
        $this->userRepository->expects($this->once())
            ->method('findExpiredTokenUsers')
            ->willReturn([]);

        $result = $this->service->refreshExpiredTokens();

        $this->assertEquals(0, $result);
    }

    public function testRefreshTokenReturnsFalse(): void
    {
        // Sina Weibo doesn't support refresh tokens
        $result = $this->service->refreshToken('test_uid');

        $this->assertFalse($result);
    }

    public function testCleanupExpiredStates(): void
    {
        $this->stateRepository->expects($this->once())
            ->method('cleanupExpiredStates')
            ->willReturn(5);

        $result = $this->service->cleanupExpiredStates();

        $this->assertEquals(5, $result);
    }

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->configRepository = $this->createMock(SinaWeiboOAuth2ConfigRepository::class);
        $this->stateRepository = $this->createMock(SinaWeiboOAuth2StateRepository::class);
        $this->userRepository = $this->createMock(SinaWeiboOAuth2UserRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->urlGenerator = $this->createMock(UrlGeneratorInterface::class);

        $this->service = new SinaWeiboOAuth2Service(
            $this->httpClient,
            $this->configRepository,
            $this->stateRepository,
            $this->userRepository,
            $this->entityManager,
            $this->urlGenerator
        );
    }
}