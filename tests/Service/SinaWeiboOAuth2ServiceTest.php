<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2ConfigurationException;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2Exception;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2ConfigRepository;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2StateRepository;
use Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2Service::class)]
#[RunTestsInSeparateProcesses]
final class SinaWeiboOAuth2ServiceTest extends AbstractIntegrationTestCase
{
    private SinaWeiboOAuth2Service $service;

    private SinaWeiboOAuth2ConfigRepository $configRepository;

    protected function onSetUp(): void
    {
        self::cleanDatabase();

        // Get real service from container
        $this->service = self::getService(SinaWeiboOAuth2Service::class);
        $this->configRepository = self::getService(SinaWeiboOAuth2ConfigRepository::class);

        // Clear existing configs explicitly
        $em = self::getEntityManager();
        $em->createQuery('DELETE FROM ' . SinaWeiboOAuth2Config::class)->execute();
        $em->createQuery('DELETE FROM ' . SinaWeiboOAuth2State::class)->execute();
        $em->createQuery('DELETE FROM ' . SinaWeiboOAuth2User::class)->execute();
        $em->flush();
    }

    public function testServiceCanBeInstantiated(): void
    {
        $this->assertInstanceOf(SinaWeiboOAuth2Service::class, $this->service);
    }

    public function testServiceHasExpectedDependencies(): void
    {
        // Create test configuration for this test
        $this->createTestConfig();

        // Test that the service can be retrieved from container with proper dependencies
        $this->assertInstanceOf(SinaWeiboOAuth2Service::class, $this->service);

        // Test that repository works and can find configs
        $allConfigs = $this->configRepository->findAll();
        $this->assertGreaterThanOrEqual(1, count($allConfigs), 'Should have at least one config');

        // Test that first config has expected properties
        $firstConfig = $allConfigs[0];
        $this->assertEquals('test_app_id', $firstConfig->getAppId());
        $this->assertTrue($firstConfig->isValid());
    }

    public function testGenerateAuthorizationUrlWithValidConfig(): void
    {
        // Create test configuration for this test
        $this->createTestConfig();
        $this->clearConfigCache();

        $url = $this->service->generateAuthorizationUrl();

        $this->assertStringStartsWith('https://api.weibo.com/oauth2/authorize', $url);
        $this->assertStringContainsString('response_type=code', $url);
        $this->assertStringContainsString('client_id=test_app_id', $url);
        $this->assertStringContainsString('scope=email', $url);
        $this->assertStringContainsString('state=', $url);
    }

    public function testGenerateAuthorizationUrlWithSessionId(): void
    {
        // Create test configuration for this test
        $this->createTestConfig();
        $this->clearConfigCache();

        $sessionId = 'test_session_123';
        $url = $this->service->generateAuthorizationUrl($sessionId);

        $this->assertStringStartsWith('https://api.weibo.com/oauth2/authorize', $url);

        // Verify that a state entity was created with the session ID
        $stateRepository = self::getService(SinaWeiboOAuth2StateRepository::class);
        $states = $stateRepository->findAll();

        $this->assertCount(1, $states);
        $this->assertEquals($sessionId, $states[0]->getSessionId());
    }

    public function testGenerateAuthorizationUrlThrowsExceptionWhenNoConfig(): void
    {
        // Clear all configs to simulate empty database - configs already cleared in onSetUp
        // Also clear cache to ensure no cached config exists
        $this->clearConfigCache();

        $this->expectException(SinaWeiboOAuth2ConfigurationException::class);
        $this->expectExceptionMessage('No valid Sina Weibo OAuth2 configuration found');

        $this->service->generateAuthorizationUrl();
    }

    public function testHandleCallbackWithValidStateAndCode(): void
    {
        // Arrange: create config and valid state
        $config = $this->createTestConfig();
        $this->clearConfigCache();

        $state = new SinaWeiboOAuth2State();
        $state->setState('valid_state_' . uniqid());
        $state->setConfig($config);
        $state->setExpiresInMinutes(10);
        $em = self::getEntityManager();
        $em->persist($state);
        $em->flush();

        // Mock HTTP client for token exchange and user info
        $httpClient = $this->createMock(HttpClientInterface::class);
        $tokenResponse = new class implements \Symfony\Contracts\HttpClient\ResponseInterface {
            public function getStatusCode(): int { return 200; }
            public function getHeaders(bool $throw = true): array { return []; }
            public function getContent(bool $throw = true): string { return (string) json_encode(['access_token' => 'test_token', 'uid' => 'u123'], JSON_THROW_ON_ERROR); }
            /**
             * @return array<string, mixed>
             */
            public function toArray(bool $throw = true): array { return ['access_token' => 'test_token', 'uid' => 'u123']; }
            public function cancel(): void {}
            public function getInfo(?string $type = null): mixed { return null; }
        };
        $userInfoResponse = new class implements \Symfony\Contracts\HttpClient\ResponseInterface {
            public function getStatusCode(): int { return 200; }
            public function getHeaders(bool $throw = true): array { return []; }
            public function getContent(bool $throw = true): string { return (string) json_encode(['screen_name' => 'nick', 'avatar_large' => 'http://a', 'gender' => 'm', 'location' => 'loc', 'description' => 'desc'], JSON_THROW_ON_ERROR); }
            /**
             * @return array<string, mixed>
             */
            public function toArray(bool $throw = true): array { return ['screen_name' => 'nick', 'avatar_large' => 'http://a', 'gender' => 'm', 'location' => 'loc', 'description' => 'desc']; }
            public function cancel(): void {}
            public function getInfo(?string $type = null): mixed { return null; }
        };

        $httpClient
            ->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function (string $method, string $url) use ($tokenResponse, $userInfoResponse) {
                if (str_contains($url, 'access_token')) {
                    return $tokenResponse;
                }
                return $userInfoResponse;
            });

        // Inject mock client
        $this->service->setHttpClient($httpClient);

        // Act
        $user = $this->service->handleCallback('code123', $state->getState());

        // Assert
        $this->assertInstanceOf(SinaWeiboOAuth2User::class, $user);
        $this->assertSame('u123', $user->getUid());
        $this->assertSame('nick', $user->getNickname());
        $this->assertSame('http://a', $user->getAvatar());
    }

    public function testHandleCallbackWithExpiredState(): void
    {
        // Create test configuration for this test
        $config = $this->createTestConfig();
        $this->clearConfigCache();

        // Create expired state with unique state value
        $expiredState = new SinaWeiboOAuth2State();
        $expiredState->setState('expired_state_' . uniqid());
        $expiredState->setConfig($config);
        $expiredState->setExpiresInMinutes(-1); // Already expired
        $em = self::getEntityManager();
        $em->persist($expiredState);
        $em->flush();

        $this->expectException(SinaWeiboOAuth2Exception::class);
        $this->expectExceptionMessage('Invalid or expired state');

        $this->service->handleCallback('test_code', $expiredState->getState());
    }

    public function testHandleCallbackThrowsExceptionForInvalidState(): void
    {
        $this->expectException(SinaWeiboOAuth2Exception::class);
        $this->expectExceptionMessage('Invalid or expired state');

        $this->service->handleCallback('test_code', 'invalid_state');
    }

    public function testRefreshTokenReturnsFalse(): void
    {
        // Sina Weibo doesn't support refresh tokens
        $result = $this->service->refreshToken('123456');

        $this->assertFalse($result);
    }

    public function testRefreshExpiredTokensReturnsZeroWhenNoExpiredUsers(): void
    {
        $result = $this->service->refreshExpiredTokens();

        $this->assertSame(0, $result);
    }

    public function testRefreshExpiredTokensWithExpiredUsers(): void
    {
        $config = $this->createTestConfig();
        $this->clearConfigCache();

        // Create an expired user with a unique UID
        $expiredUser = new SinaWeiboOAuth2User();
        $expiredUser->setUid('test_uid_' . uniqid());
        $expiredUser->setAccessToken('expired_token');
        $expiredUser->setExpiresIn(1);
        $expiredUser->setConfig($config);
        // Set token to be expired by modifying create time
        $reflection = new \ReflectionClass($expiredUser);
        $createTimeProperty = $reflection->getProperty('createTime');
        $createTimeProperty->setAccessible(true);
        $createTimeProperty->setValue($expiredUser, new \DateTimeImmutable('-2 hours'));

        $em = self::getEntityManager();
        $em->persist($expiredUser);
        $em->flush();

        $result = $this->service->refreshExpiredTokens();

        // Should return 0 since Sina Weibo doesn't support refresh tokens
        $this->assertSame(0, $result);
    }

    public function testCleanupExpiredStates(): void
    {
        $config = $this->createTestConfig();
        $this->clearConfigCache();

        $em = self::getEntityManager();

        // Create expired state with unique state value
        $expiredState = new SinaWeiboOAuth2State();
        $expiredState->setState('expired_state_' . uniqid());
        $expiredState->setConfig($config);
        $expiredState->setExpiresInMinutes(-1); // Already expired
        $em->persist($expiredState);

        // Create used state with unique state value
        $usedState = new SinaWeiboOAuth2State();
        $usedState->setState('used_state_' . uniqid());
        $usedState->setConfig($config);
        $usedState->setExpiresInMinutes(10);
        $usedState->markAsUsed();
        $em->persist($usedState);

        $em->flush();

        $result = $this->service->cleanupExpiredStates();

        // Should clean up at least the expired and used states
        $this->assertGreaterThanOrEqual(2, $result);
    }

    public function testCleanupExpiredStatesReturnsZeroWhenNoExpiredStates(): void
    {
        $result = $this->service->cleanupExpiredStates();

        $this->assertSame(0, $result);
    }

    private function createTestConfig(): SinaWeiboOAuth2Config
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_id');
        $config->setAppSecret('test_app_secret');
        $config->setScope('email');
        $config->setValid(true);

        $em = self::getEntityManager();
        $em->persist($config);
        $em->flush();

        return $config;
    }

    private function clearConfigCache(): void
    {
        $configRepository = self::getService(SinaWeiboOAuth2ConfigRepository::class);
        $reflection = new \ReflectionClass($configRepository);
        $cacheProperty = $reflection->getProperty('cache');
        $cacheProperty->setAccessible(true);
        $cache = $cacheProperty->getValue($configRepository);

        if (null !== $cache && is_object($cache) && method_exists($cache, 'delete')) {
            $cache->delete('sina_weibo_oauth2_valid_config');
        }
    }
}
