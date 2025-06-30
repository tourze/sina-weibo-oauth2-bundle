<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Integration\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;
use Tourze\SinaWeiboOAuth2Bundle\Tests\TestKernel;

class SinaWeiboOAuth2LoginControllerTest extends WebTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    private function setupDatabase(): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($em);
        
        $classes = [
            $em->getClassMetadata(SinaWeiboOAuth2Config::class),
            $em->getClassMetadata(SinaWeiboOAuth2State::class),
            $em->getClassMetadata(SinaWeiboOAuth2User::class),
        ];
        
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
    }

    public function testLoginWithoutConfig(): void
    {
        $client = static::createClient();
        $this->setupDatabase();
        
        // 确保没有有效配置 - 清理数据库
        $em = self::getContainer()->get('doctrine')->getManager();
        $qb = $em->createQueryBuilder();
        $qb->delete(SinaWeiboOAuth2Config::class, 'c');
        $qb->getQuery()->execute();
        $em->flush();
        $em->clear();
        
        // 清除配置缓存
        $configRepository = self::getContainer()->get(\Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2ConfigRepository::class);
        $configRepository->invalidateCache();
        
        $this->expectException(\Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2ConfigurationException::class);
        
        $client->catchExceptions(false);
        $client->request('GET', '/sina-weibo-oauth2/login');
    }
}