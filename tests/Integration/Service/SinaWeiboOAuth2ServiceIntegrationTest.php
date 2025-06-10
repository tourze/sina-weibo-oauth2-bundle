<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Integration\Service;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboAppConfig;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Token;
use Tourze\SinaWeiboOAuth2Bundle\Exception\AuthorizationException;
use Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service;
use Tourze\SinaWeiboOAuth2Bundle\SinaWeiboOAuth2Bundle;

class SinaWeiboOAuth2ServiceIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private SinaWeiboOAuth2Service $oauth2Service;
    private SinaWeiboAppConfig $testAppConfig;

    protected static function createKernel(array $options = []): KernelInterface
    {
        return new IntegrationTestKernel('test', true, [
            SinaWeiboOAuth2Bundle::class => ['all' => true],
        ]);
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->oauth2Service = static::getContainer()->get(SinaWeiboOAuth2Service::class);

        // 创建数据库表结构
        $this->createSchema();

        // 创建测试用的应用配置
        $this->createTestAppConfig();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function createSchema(): void
    {
        $metadataFactory = $this->entityManager->getMetadataFactory();
        $metadata = [
            $metadataFactory->getMetadataFor(SinaWeiboAppConfig::class),
            $metadataFactory->getMetadataFor(SinaWeiboOAuth2Token::class),
        ];
        
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $schemaTool->createSchema($metadata);
    }

    private function createTestAppConfig(): void
    {
        $this->testAppConfig = new SinaWeiboAppConfig();
        $this->testAppConfig->setAppName('Integration Test App')
            ->setAppKey('integration_test_key')
            ->setAppSecret('integration_test_secret')
            ->setRedirectUri('http://example.com/callback')
            ->setScope('email,statuses_to_me_read')
            ->setValid(true);

        $this->entityManager->persist($this->testAppConfig);
        $this->entityManager->flush();
    }

    public function test_getAuthorizeUrl_withValidAppConfig(): void
    {
        $url = $this->oauth2Service->getAuthorizeUrl(
            $this->testAppConfig->getAppKey(),
            null,
            null,
            'test_state'
        );

        $this->assertStringContainsString('https://api.weibo.com/oauth2/authorize', $url);
        $this->assertStringContainsString('client_id=integration_test_key', $url);
        $this->assertStringContainsString('state=test_state', $url);
        $this->assertStringContainsString('scope=email%2Cstatuses_to_me_read', $url);
    }

    public function test_getAuthorizeUrl_withInvalidAppKey(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('应用配置不存在');

        $this->oauth2Service->getAuthorizeUrl('nonexistent_app_key');
    }

    public function test_getAuthorizeUrl_withInactiveApp(): void
    {
        // 创建无效的应用配置
        $inactiveApp = new SinaWeiboAppConfig();
        $inactiveApp->setAppName('Inactive App')
            ->setAppKey('inactive_app_key')
            ->setAppSecret('inactive_secret')
            ->setRedirectUri('http://example.com/callback')
            ->setValid(false); // 设置为无效

        $this->entityManager->persist($inactiveApp);
        $this->entityManager->flush();

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('应用配置未激活');

        $this->oauth2Service->getAuthorizeUrl('inactive_app_key');
    }

    public function test_getDefaultAuthorizeUrl(): void
    {
        $result = $this->oauth2Service->getDefaultAuthorizeUrl($this->testAppConfig->getAppKey());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('state', $result);

        $this->assertStringContainsString('https://api.weibo.com/oauth2/authorize', $result['url']);
        $this->assertStringContainsString('state=' . $result['state'], $result['url']);
        $this->assertEquals(32, strlen($result['state'])); // 16字节转hex = 32字符
    }

    public function test_startAuthorization(): void
    {
        $result = $this->oauth2Service->startAuthorization($this->testAppConfig->getAppKey());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('url', $result);
        $this->assertArrayHasKey('state', $result);

        // 验证URL包含必要参数
        $this->assertStringContainsString('client_id=integration_test_key', $result['url']);
        $this->assertStringContainsString('response_type=code', $result['url']);
        $this->assertIsString($result['state']);
        $this->assertNotEmpty($result['state']);
    }

    public function test_ensureValidToken_withValidToken(): void
    {
        // 创建有效令牌
        $token = new SinaWeiboOAuth2Token();
        $token->setAppConfig($this->testAppConfig)
            ->setWeiboUid('valid_user')
            ->setAccessToken('valid_access_token')
            ->setExpiresTime(new \DateTime('+1 hour'))
            ->setValid(true);

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $validToken = $this->oauth2Service->ensureValidToken($token);

        $this->assertSame($token, $validToken);
        $this->assertTrue($validToken->isValid());
        $this->assertTrue($validToken->isTokenValid());
    }

    public function test_ensureValidToken_withExpiredTokenButValidRefresh(): void
    {
        // 创建过期的访问令牌但有有效的刷新令牌
        $token = new SinaWeiboOAuth2Token();
        $token->setAppConfig($this->testAppConfig)
            ->setWeiboUid('refresh_user')
            ->setAccessToken('expired_access_token')
            ->setExpiresTime(new \DateTime('-1 hour'))
            ->setRefreshToken('valid_refresh_token')
            ->setRefreshExpiresTime(new \DateTime('+30 days'))
            ->setValid(true);

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        // 注意：这个测试会尝试调用真实的API来刷新令牌
        // 在没有网络连接或API密钥无效的情况下会抛出异常
        $this->expectException(\Exception::class);

        $this->oauth2Service->ensureValidToken($token);
    }

    public function test_ensureValidToken_withCompletelyInvalidToken(): void
    {
        // 创建完全无效的令牌（没有刷新令牌或刷新令牌也过期）
        $token = new SinaWeiboOAuth2Token();
        $token->setAppConfig($this->testAppConfig)
            ->setWeiboUid('invalid_user')
            ->setAccessToken('invalid_access_token')
            ->setExpiresTime(new \DateTime('-1 hour'))
            ->setRefreshToken('expired_refresh_token')
            ->setRefreshExpiresTime(new \DateTime('-1 day'))
            ->setValid(true);

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('令牌已过期且无法刷新，需要重新授权');

        $this->oauth2Service->ensureValidToken($token);
    }

    public function test_service_integrates_with_repository_correctly(): void
    {
        // 验证服务能够正确地与Repository交互

        // 创建多个应用配置
        $app1 = new SinaWeiboAppConfig();
        $app1->setAppName('App 1')
            ->setAppKey('app_key_1')
            ->setAppSecret('app_secret_1')
            ->setRedirectUri('http://app1.example.com/callback')
            ->setValid(true);

        $app2 = new SinaWeiboAppConfig();
        $app2->setAppName('App 2')
            ->setAppKey('app_key_2')
            ->setAppSecret('app_secret_2')
            ->setRedirectUri('http://app2.example.com/callback')
            ->setValid(false); // 无效应用

        $this->entityManager->persist($app1);
        $this->entityManager->persist($app2);
        $this->entityManager->flush();

        // 测试1：有效应用应该能生成授权URL
        $url1 = $this->oauth2Service->getAuthorizeUrl('app_key_1');
        $this->assertStringContainsString('client_id=app_key_1', $url1);

        // 测试2：无效应用应该抛出异常
        $this->expectException(AuthorizationException::class);
        $this->oauth2Service->getAuthorizeUrl('app_key_2');
    }

    public function test_service_handles_multiple_app_configs(): void
    {
        // 测试服务处理多个应用配置的能力

        $productionApp = new SinaWeiboAppConfig();
        $productionApp->setAppName('Production App')
            ->setAppKey('prod_app_key')
            ->setAppSecret('prod_secret')
            ->setRedirectUri('https://production.example.com/callback')
            ->setScope('email')
            ->setValid(true);

        $testApp = new SinaWeiboAppConfig();
        $testApp->setAppName('Test App')
            ->setAppKey('test_app_key')
            ->setAppSecret('test_secret')
            ->setRedirectUri('https://test.example.com/callback')
            ->setScope('email,statuses_to_me_read')
            ->setValid(true);

        $this->entityManager->persist($productionApp);
        $this->entityManager->persist($testApp);
        $this->entityManager->flush();

        // 验证不同应用生成不同的授权URL
        $prodUrl = $this->oauth2Service->getAuthorizeUrl('prod_app_key');
        $testUrl = $this->oauth2Service->getAuthorizeUrl('test_app_key');

        $this->assertStringContainsString('client_id=prod_app_key', $prodUrl);
        $this->assertStringContainsString('client_id=test_app_key', $testUrl);

        // 验证scope参数的差异
        $this->assertStringContainsString('scope=email', $prodUrl);
        $this->assertStringNotContainsString('statuses_to_me_read', $prodUrl);

        $this->assertStringContainsString('scope=email%2Cstatuses_to_me_read', $testUrl);
    }
} 