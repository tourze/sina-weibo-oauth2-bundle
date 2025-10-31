<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2ConfigRepository;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2ConfigRepository::class)]
#[RunTestsInSeparateProcesses]
final class SinaWeiboOAuth2ConfigRepositoryTest extends AbstractRepositoryTestCase
{
    private SinaWeiboOAuth2ConfigRepository $repository;

    public function testFindValidConfigReturnsNullWhenNoValidConfig(): void
    {
        $em = self::getEntityManager();
        $existingConfigs = $this->repository->findAll();
        foreach ($existingConfigs as $config) {
            $em->remove($config);
        }
        $em->flush();

        $this->repository->invalidateCache();

        $result = $this->repository->findValidConfig();

        $this->assertNull($result);
    }

    public function testFindValidConfigReturnsValidConfig(): void
    {
        $config1 = new SinaWeiboOAuth2Config();
        $config1->setAppId('app1');
        $config1->setAppSecret('secret1');
        $config1->setScope(null);
        $config1->setValid(true);

        $this->persistAndFlush($config1);

        $this->repository->invalidateCache();

        $result = $this->repository->findValidConfig();

        $this->assertNotNull($result);
        $this->assertSame('app1', $result->getAppId());
        $this->assertTrue($result->isValid());
    }

    public function testFindValidConfigWithCacheReturnsFromDatabase(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('get')
            ->willReturnCallback(function (string $key, callable $callback) {
                return $callback($this->createMock(ItemInterface::class));
            })
        ;

        // 直接替换仓库中的缓存实现，避免 TestContainer 对已初始化服务的限制
        $repository = self::getService(SinaWeiboOAuth2ConfigRepository::class);
        $repository->setCache($cache);

        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('cached_app');
        $config->setAppSecret('cached_secret');
        $config->setScope(null);
        $config->setValid(true);

        $this->persistAndFlush($config);

        $result = $repository->findValidConfig();

        $this->assertNotNull($result);
        $this->assertSame('cached_app', $result->getAppId());
    }

    public function testFindActiveConfigsReturnsOnlyValidConfigs(): void
    {
        $em = self::getEntityManager();
        $existingConfigs = $this->repository->findAll();
        foreach ($existingConfigs as $config) {
            $em->remove($config);
        }
        $em->flush();

        $validConfig1 = new SinaWeiboOAuth2Config();
        $validConfig1->setAppId('valid1');
        $validConfig1->setAppSecret('secret1');
        $validConfig1->setScope(null);
        $validConfig1->setValid(true);

        $validConfig2 = new SinaWeiboOAuth2Config();
        $validConfig2->setAppId('valid2');
        $validConfig2->setAppSecret('secret2');
        $validConfig2->setScope(null);
        $validConfig2->setValid(true);

        $invalidConfig = new SinaWeiboOAuth2Config();
        $invalidConfig->setAppId('invalid');
        $invalidConfig->setAppSecret('secret_invalid');
        $invalidConfig->setScope(null);
        $invalidConfig->setValid(false);

        $this->persistAndFlush($validConfig1);
        $this->persistAndFlush($validConfig2);
        $this->persistAndFlush($invalidConfig);

        $results = $this->repository->findActiveConfigs();

        $this->assertCount(2, $results);
        $appIds = array_map(fn ($config) => $config->getAppId(), $results);
        $this->assertContains('valid1', $appIds);
        $this->assertContains('valid2', $appIds);
        $this->assertNotContains('invalid', $appIds);
    }

    public function testSaveEntityPersistsConfig(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app');
        $config->setAppSecret('test_secret');
        $config->setScope('email,user_show');
        $config->setValid(true);

        $this->repository->save($config);

        $this->assertEntityPersisted($config);

        $found = self::getEntityManager()->find(SinaWeiboOAuth2Config::class, $config->getId());
        $this->assertNotNull($found);
        $this->assertSame('test_app', $found->getAppId());
        $this->assertSame('test_secret', $found->getAppSecret());
        $this->assertSame('email,user_show', $found->getScope());
        $this->assertTrue($found->isValid());
    }

    public function testSaveEntityWithoutFlushDoesNotPersistImmediately(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_no_flush');
        $config->setAppSecret('test_secret');

        $this->repository->save($config, false);

        $em = self::getEntityManager();

        $allConfigs = $this->repository->findBy(['appId' => 'test_app_no_flush']);
        $this->assertEmpty($allConfigs);

        $em->flush();

        $allConfigs = $this->repository->findBy(['appId' => 'test_app_no_flush']);
        $this->assertCount(1, $allConfigs);
        $this->assertSame('test_app_no_flush', $allConfigs[0]->getAppId());
    }

    public function testRemoveEntityDeletesConfig(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('to_delete');
        $config->setAppSecret('secret_delete');

        $this->persistAndFlush($config);
        $configId = $config->getId();

        $this->repository->remove($config);

        $this->assertEntityNotExists(SinaWeiboOAuth2Config::class, $configId);

        $found = self::getEntityManager()->find(SinaWeiboOAuth2Config::class, $configId);
        $this->assertNull($found);
    }

    public function testRemoveEntityWithoutFlushDoesNotDeleteImmediately(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('to_delete_no_flush');
        $config->setAppSecret('secret_delete');

        $this->persistAndFlush($config);
        $configId = $config->getId();

        $this->repository->remove($config, false);

        $found = self::getEntityManager()->find(SinaWeiboOAuth2Config::class, $configId);
        $this->assertNotNull($found);

        self::getEntityManager()->flush();
        $this->assertEntityNotExists(SinaWeiboOAuth2Config::class, $configId);
    }

    public function testInvalidateCacheCallsDeleteOnCache(): void
    {
        $cache = $this->createMock(CacheInterface::class);
        $cache->expects($this->once())
            ->method('delete')
            ->with('sina_weibo_oauth2_valid_config')
        ;

        $repository = self::getService(SinaWeiboOAuth2ConfigRepository::class);
        $repository->setCache($cache);
        $repository->invalidateCache();
    }

    public function testInvalidateCacheDoesNotThrow(): void
    {
        $repository = self::getService(SinaWeiboOAuth2ConfigRepository::class);
        $repository->invalidateCache();
        $this->expectNotToPerformAssertions();
    }

    public function testFindActiveConfigsOrdersByCreateTimeDesc(): void
    {
        $em = self::getEntityManager();
        $existingConfigs = $this->repository->findAll();
        foreach ($existingConfigs as $config) {
            $em->remove($config);
        }
        $em->flush();

        $now = new \DateTimeImmutable();

        $config1 = new SinaWeiboOAuth2Config();
        $config1->setAppId('first');
        $config1->setAppSecret('secret1');
        $config1->setScope(null);
        $config1->setValid(true);
        $config1->setCreateTime($now->modify('-1 minute'));

        $config2 = new SinaWeiboOAuth2Config();
        $config2->setAppId('second');
        $config2->setAppSecret('secret2');
        $config2->setScope(null);
        $config2->setValid(true);
        $config2->setCreateTime($now);

        $this->persistAndFlush($config1);
        $this->persistAndFlush($config2);

        $results = $this->repository->findActiveConfigs();

        $this->assertCount(2, $results);
        $this->assertSame('second', $results[0]->getAppId());
        $this->assertSame('first', $results[1]->getAppId());
    }

    protected function onSetUp(): void
    {
        $this->repository = self::getService(SinaWeiboOAuth2ConfigRepository::class);
    }

    protected function createNewEntity(): object
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_' . uniqid());
        $config->setAppSecret('test_secret_' . uniqid());
        $config->setScope(null);
        $config->setValid(true);

        return $config;
    }

    /**
     * @return ServiceEntityRepository<SinaWeiboOAuth2Config>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
