<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Integration\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboAppConfig;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboAppConfigRepository;
use Tourze\SinaWeiboOAuth2Bundle\SinaWeiboOAuth2Bundle;

class SinaWeiboAppConfigRepositoryIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private SinaWeiboAppConfigRepository $repository;

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
        $this->repository = static::getContainer()->get(SinaWeiboAppConfigRepository::class);

        // 创建数据库表结构
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function createSchema(): void
    {
        $metadataFactory = $this->entityManager->getMetadataFactory();
        $metadata = $metadataFactory->getMetadataFor(SinaWeiboAppConfig::class);
        
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $schemaTool->createSchema([$metadata]);
    }

    public function test_findByAppKey_withExistingApp(): void
    {
        // 准备测试数据
        $appConfig = new SinaWeiboAppConfig();
        $appConfig->setAppName('Test App')
            ->setAppKey('test_app_key')
            ->setAppSecret('test_secret')
            ->setRedirectUri('http://example.com/callback')
            ->setScope('email')
            ->setValid(true);

        $this->entityManager->persist($appConfig);
        $this->entityManager->flush();

        // 执行测试
        $foundApp = $this->repository->findByAppKey('test_app_key');

        // 验证结果
        $this->assertNotNull($foundApp);
        $this->assertEquals('Test App', $foundApp->getAppName());
        $this->assertEquals('test_app_key', $foundApp->getAppKey());
        $this->assertEquals('test_secret', $foundApp->getAppSecret());
    }

    public function test_findByAppKey_withNonExistentApp(): void
    {
        $foundApp = $this->repository->findByAppKey('nonexistent_key');
        $this->assertNull($foundApp);
    }

    public function test_findAllValid_withMixedValidityApps(): void
    {
        // 创建多个应用配置，部分有效、部分无效
        $validApp1 = new SinaWeiboAppConfig();
        $validApp1->setAppName('Valid App 1')
            ->setAppKey('valid_key_1')
            ->setAppSecret('secret1')
            ->setRedirectUri('http://example.com/callback1')
            ->setValid(true);

        $validApp2 = new SinaWeiboAppConfig();
        $validApp2->setAppName('Valid App 2')
            ->setAppKey('valid_key_2')
            ->setAppSecret('secret2')
            ->setRedirectUri('http://example.com/callback2')
            ->setValid(true);

        $invalidApp = new SinaWeiboAppConfig();
        $invalidApp->setAppName('Invalid App')
            ->setAppKey('invalid_key')
            ->setAppSecret('secret3')
            ->setRedirectUri('http://example.com/callback3')
            ->setValid(false);

        $this->entityManager->persist($validApp1);
        $this->entityManager->persist($validApp2);
        $this->entityManager->persist($invalidApp);
        $this->entityManager->flush();

        // 执行测试
        $validApps = $this->repository->findAllValid();

        // 验证结果
        $this->assertCount(2, $validApps);
        $appKeys = array_map(fn($app) => $app->getAppKey(), $validApps);
        $this->assertContains('valid_key_1', $appKeys);
        $this->assertContains('valid_key_2', $appKeys);
        $this->assertNotContains('invalid_key', $appKeys);
    }

    public function test_findValidByAppKey_withValidApp(): void
    {
        // 创建有效应用
        $validApp = new SinaWeiboAppConfig();
        $validApp->setAppName('Valid App')
            ->setAppKey('valid_key')
            ->setAppSecret('secret')
            ->setRedirectUri('http://example.com/callback')
            ->setValid(true);

        $this->entityManager->persist($validApp);
        $this->entityManager->flush();

        $foundApp = $this->repository->findValidByAppKey('valid_key');

        $this->assertNotNull($foundApp);
        $this->assertEquals('Valid App', $foundApp->getAppName());
        $this->assertTrue($foundApp->isValid());
    }

    public function test_findValidByAppKey_withInvalidApp(): void
    {
        // 创建无效应用
        $invalidApp = new SinaWeiboAppConfig();
        $invalidApp->setAppName('Invalid App')
            ->setAppKey('invalid_key')
            ->setAppSecret('secret')
            ->setRedirectUri('http://example.com/callback')
            ->setValid(false);

        $this->entityManager->persist($invalidApp);
        $this->entityManager->flush();

        $foundApp = $this->repository->findValidByAppKey('invalid_key');

        $this->assertNull($foundApp);
    }

    public function test_persist_and_flush_appConfig(): void
    {
        $appConfig = new SinaWeiboAppConfig();
        $appConfig->setAppName('Persistence Test App')
            ->setAppKey('persistence_key')
            ->setAppSecret('persistence_secret')
            ->setRedirectUri('http://example.com/persistence')
            ->setScope('email,statuses_to_me_read')
            ->setValid(true)
            ->setRemark('Test remark');

        // 持久化到数据库
        $this->entityManager->persist($appConfig);
        $this->entityManager->flush();

        // 验证时间戳字段是否自动设置
        $this->assertNotNull($appConfig->getCreateTime());
        $this->assertNotNull($appConfig->getUpdateTime());

        // 清空实体管理器缓存，从数据库重新查询
        $this->entityManager->clear();
        $foundApp = $this->repository->findByAppKey('persistence_key');

        $this->assertNotNull($foundApp);
        $this->assertEquals('Persistence Test App', $foundApp->getAppName());
        $this->assertEquals('email,statuses_to_me_read', $foundApp->getScope());
        $this->assertEquals('Test remark', $foundApp->getRemark());
    }
} 