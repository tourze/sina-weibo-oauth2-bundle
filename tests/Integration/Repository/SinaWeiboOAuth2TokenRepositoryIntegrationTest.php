<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Integration\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboAppConfig;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Token;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2TokenRepository;
use Tourze\SinaWeiboOAuth2Bundle\SinaWeiboOAuth2Bundle;

class SinaWeiboOAuth2TokenRepositoryIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private SinaWeiboOAuth2TokenRepository $repository;
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
        $this->repository = static::getContainer()->get(SinaWeiboOAuth2TokenRepository::class);

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
        $this->testAppConfig->setAppName('Test App')
            ->setAppKey('test_app_key')
            ->setAppSecret('test_secret')
            ->setRedirectUri('http://example.com/callback')
            ->setValid(true);

        $this->entityManager->persist($this->testAppConfig);
        $this->entityManager->flush();
    }

    public function test_findByAppConfigAndWeiboUid_withExistingToken(): void
    {
        // 创建测试令牌
        $token = new SinaWeiboOAuth2Token();
        $token->setAppConfig($this->testAppConfig)
            ->setWeiboUid('123456789')
            ->setAccessToken('test_access_token')
            ->setValid(true);

        $this->entityManager->persist($token);
        $this->entityManager->flush();

        // 执行测试
        $foundToken = $this->repository->findByAppConfigAndWeiboUid($this->testAppConfig, '123456789');

        // 验证结果
        $this->assertNotNull($foundToken);
        $this->assertEquals('123456789', $foundToken->getWeiboUid());
        $this->assertEquals('test_access_token', $foundToken->getAccessToken());
        $this->assertSame($this->testAppConfig, $foundToken->getAppConfig());
    }

    public function test_findByAppConfigAndWeiboUid_withNonExistentToken(): void
    {
        $foundToken = $this->repository->findByAppConfigAndWeiboUid($this->testAppConfig, 'nonexistent_uid');
        $this->assertNull($foundToken);
    }

    public function test_findValidTokenByAppConfigAndWeiboUid_withValidToken(): void
    {
        // 创建有效令牌
        $validToken = new SinaWeiboOAuth2Token();
        $validToken->setAppConfig($this->testAppConfig)
            ->setWeiboUid('valid_uid')
            ->setAccessToken('valid_token')
            ->setExpiresTime(new \DateTime('+1 hour'))
            ->setValid(true);

        $this->entityManager->persist($validToken);
        $this->entityManager->flush();

        $foundToken = $this->repository->findValidTokenByAppConfigAndWeiboUid($this->testAppConfig, 'valid_uid');

        $this->assertNotNull($foundToken);
        $this->assertTrue($foundToken->isValid());
        $this->assertTrue($foundToken->isTokenValid());
    }

    public function test_findValidTokenByAppConfigAndWeiboUid_withExpiredToken(): void
    {
        // 创建过期令牌
        $expiredToken = new SinaWeiboOAuth2Token();
        $expiredToken->setAppConfig($this->testAppConfig)
            ->setWeiboUid('expired_uid')
            ->setAccessToken('expired_token')
            ->setExpiresTime(new \DateTime('-1 hour'))
            ->setValid(true);

        $this->entityManager->persist($expiredToken);
        $this->entityManager->flush();

        $foundToken = $this->repository->findValidTokenByAppConfigAndWeiboUid($this->testAppConfig, 'expired_uid');

        $this->assertNull($foundToken);
    }

    public function test_findExpiredTokens(): void
    {
        // 创建多个令牌，部分过期
        $validToken = new SinaWeiboOAuth2Token();
        $validToken->setAppConfig($this->testAppConfig)
            ->setWeiboUid('valid_uid')
            ->setAccessToken('valid_token')
            ->setExpiresTime(new \DateTime('+1 hour'))
            ->setValid(true);

        $expiredToken1 = new SinaWeiboOAuth2Token();
        $expiredToken1->setAppConfig($this->testAppConfig)
            ->setWeiboUid('expired_uid_1')
            ->setAccessToken('expired_token_1')
            ->setExpiresTime(new \DateTime('-1 hour'))
            ->setValid(true);

        $expiredToken2 = new SinaWeiboOAuth2Token();
        $expiredToken2->setAppConfig($this->testAppConfig)
            ->setWeiboUid('expired_uid_2')
            ->setAccessToken('expired_token_2')
            ->setExpiresTime(new \DateTime('-2 hours'))
            ->setValid(true);

        $noExpirationToken = new SinaWeiboOAuth2Token();
        $noExpirationToken->setAppConfig($this->testAppConfig)
            ->setWeiboUid('no_exp_uid')
            ->setAccessToken('no_exp_token')
            ->setExpiresTime(null) // 永不过期
            ->setValid(true);

        $this->entityManager->persist($validToken);
        $this->entityManager->persist($expiredToken1);
        $this->entityManager->persist($expiredToken2);
        $this->entityManager->persist($noExpirationToken);
        $this->entityManager->flush();

        // 执行测试
        $expiredTokens = $this->repository->findExpiredTokens();

        // 验证结果
        $this->assertCount(2, $expiredTokens);
        $uids = array_map(fn($token) => $token->getWeiboUid(), $expiredTokens);
        $this->assertContains('expired_uid_1', $uids);
        $this->assertContains('expired_uid_2', $uids);
        $this->assertNotContains('valid_uid', $uids);
        $this->assertNotContains('no_exp_uid', $uids);
    }

    public function test_markExpiredTokensAsInvalid(): void
    {
        // 创建过期令牌
        $expiredToken = new SinaWeiboOAuth2Token();
        $expiredToken->setAppConfig($this->testAppConfig)
            ->setWeiboUid('expired_uid')
            ->setAccessToken('expired_token')
            ->setExpiresTime(new \DateTime('-1 hour'))
            ->setValid(true);

        $validToken = new SinaWeiboOAuth2Token();
        $validToken->setAppConfig($this->testAppConfig)
            ->setWeiboUid('valid_uid')
            ->setAccessToken('valid_token')
            ->setExpiresTime(new \DateTime('+1 hour'))
            ->setValid(true);

        $this->entityManager->persist($expiredToken);
        $this->entityManager->persist($validToken);
        $this->entityManager->flush();

        // 执行批量标记
        $affectedRows = $this->repository->markExpiredTokensAsInvalid();

        // 验证结果
        $this->assertEquals(1, $affectedRows);

        // 刷新实体状态
        $this->entityManager->refresh($expiredToken);
        $this->entityManager->refresh($validToken);

        $this->assertFalse($expiredToken->isValid());
        $this->assertTrue($validToken->isValid());
    }

    public function test_deleteInvalidTokensBefore(): void
    {
        $cutoffDate = new \DateTime('-1 day');

        // 创建旧的无效令牌（应该被删除）
        $oldInvalidToken = new SinaWeiboOAuth2Token();
        $oldInvalidToken->setAppConfig($this->testAppConfig)
            ->setWeiboUid('old_invalid_uid')
            ->setAccessToken('old_invalid_token')
            ->setValid(false)
            ->setUpdateTime(new \DateTime('-2 days'));

        // 创建新的无效令牌（不应该被删除）
        $newInvalidToken = new SinaWeiboOAuth2Token();
        $newInvalidToken->setAppConfig($this->testAppConfig)
            ->setWeiboUid('new_invalid_uid')
            ->setAccessToken('new_invalid_token')
            ->setValid(false)
            ->setUpdateTime(new \DateTime('-1 hour'));

        // 创建有效令牌（不应该被删除）
        $validToken = new SinaWeiboOAuth2Token();
        $validToken->setAppConfig($this->testAppConfig)
            ->setWeiboUid('valid_uid')
            ->setAccessToken('valid_token')
            ->setValid(true)
            ->setUpdateTime(new \DateTime('-3 days'));

        $this->entityManager->persist($oldInvalidToken);
        $this->entityManager->persist($newInvalidToken);
        $this->entityManager->persist($validToken);
        $this->entityManager->flush();

        // 执行删除操作
        $deletedCount = $this->repository->deleteInvalidTokensBefore($cutoffDate);

        // 验证结果
        $this->assertEquals(1, $deletedCount);

        // 验证剩余令牌
        $remainingTokens = $this->repository->findAll();
        $this->assertCount(2, $remainingTokens);

        $uids = array_map(fn($token) => $token->getWeiboUid(), $remainingTokens);
        $this->assertContains('new_invalid_uid', $uids);
        $this->assertContains('valid_uid', $uids);
        $this->assertNotContains('old_invalid_uid', $uids);
    }

    public function test_tokenValidityMethods(): void
    {
        // 创建不同状态的令牌
        $validToken = new SinaWeiboOAuth2Token();
        $validToken->setAppConfig($this->testAppConfig)
            ->setWeiboUid('valid_uid')
            ->setAccessToken('valid_token')
            ->setExpiresTime(new \DateTime('+1 hour'))
            ->setRefreshToken('refresh_token')
            ->setRefreshExpiresTime(new \DateTime('+30 days'))
            ->setValid(true);

        $this->entityManager->persist($validToken);
        $this->entityManager->flush();

        // 验证令牌有效性方法
        $this->assertTrue($validToken->isValid());
        $this->assertTrue($validToken->isTokenValid());
        $this->assertTrue($validToken->isRefreshTokenValid());

        // 修改为过期状态
        $validToken->setExpiresTime(new \DateTime('-1 hour'));
        $this->assertFalse($validToken->isTokenValid());
        $this->assertTrue($validToken->isRefreshTokenValid()); // 刷新令牌仍有效
    }
} 