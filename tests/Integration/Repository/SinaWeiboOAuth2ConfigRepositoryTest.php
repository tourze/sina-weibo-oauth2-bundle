<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Integration\Repository;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2ConfigRepository;
use Tourze\SinaWeiboOAuth2Bundle\Tests\TestKernel;

class SinaWeiboOAuth2ConfigRepositoryTest extends KernelTestCase
{
    private SinaWeiboOAuth2ConfigRepository $repository;

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(SinaWeiboOAuth2ConfigRepository::class);
        $this->setupDatabase();
    }

    private function setupDatabase(): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($em);
        
        $classes = [
            $em->getClassMetadata(SinaWeiboOAuth2Config::class),
            $em->getClassMetadata(\Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State::class),
            $em->getClassMetadata(\Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User::class),
        ];
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
        
        // 确保数据库为空
        $em->clear();
    }

    public function testFindValidConfig(): void
    {
        // 清理所有现有配置
        $em = self::getContainer()->get('doctrine')->getManager();
        $qb = $em->createQueryBuilder();
        $qb->delete(SinaWeiboOAuth2Config::class, 'c');
        $qb->getQuery()->execute();
        $em->flush();
        $em->clear();
        
        // 清除缓存
        $this->repository->invalidateCache();
        
        // 测试在没有配置时应该返回 null
        $config = $this->repository->findValidConfig();
        $this->assertNull($config);
        
        // 创建一个有效配置并测试能找到它
        $validConfig = new SinaWeiboOAuth2Config();
        $validConfig->setAppId('test_valid_app_id');
        $validConfig->setAppSecret('test_valid_secret');
        $validConfig->setValid(true);
        
        $em->persist($validConfig);
        $em->flush();
        $em->clear(); // 清除实体管理器缓存
        
        // 清除缓存以确保从数据库读取
        $this->repository->invalidateCache();
        
        $foundConfig = $this->repository->findValidConfig();
        $this->assertNotNull($foundConfig);
        $this->assertEquals('test_valid_app_id', $foundConfig->getAppId());
    }

    public function testFindActiveConfigs(): void
    {
        $configs = $this->repository->findActiveConfigs();
        $this->assertEmpty($configs);
    }
}