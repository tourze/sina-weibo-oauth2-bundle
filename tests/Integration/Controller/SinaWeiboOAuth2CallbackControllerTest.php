<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Integration\Controller;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;
use Tourze\SinaWeiboOAuth2Bundle\Tests\TestKernel;

class SinaWeiboOAuth2CallbackControllerTest extends WebTestCase
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

    public function testCallbackWithInvalidParameters(): void
    {
        $client = static::createClient();
        $this->setupDatabase();
        
        $client->request('GET', '/sina-weibo-oauth2/callback');
        
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }

    public function testCallbackWithError(): void
    {
        $client = static::createClient();
        $this->setupDatabase();
        
        $client->request('GET', '/sina-weibo-oauth2/callback', [
            'error' => 'access_denied',
            'error_description' => 'User denied'
        ]);
        
        $this->assertEquals(400, $client->getResponse()->getStatusCode());
    }
}