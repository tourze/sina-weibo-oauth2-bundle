<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;

class SinaWeiboOAuth2ConfigTest extends TestCase
{
    public function testConstructorAndGetters(): void
    {
        $config = new SinaWeiboOAuth2Config();
        
        $this->assertNull($config->getId());
        $this->assertTrue($config->isActive());
        // Timestamps are null until persisted
        $this->assertNull($config->getCreatedAt());
        $this->assertNull($config->getUpdatedAt());
    }

    public function testSettersAndGetters(): void
    {
        $config = new SinaWeiboOAuth2Config();
        
        $config->setAppId('test_app_id')
            ->setAppSecret('test_app_secret')
            ->setScope('email,profile')
            ->setIsActive(false);

        $this->assertEquals('test_app_id', $config->getAppId());
        $this->assertEquals('test_app_secret', $config->getAppSecret());
        $this->assertEquals('email,profile', $config->getScope());
        $this->assertFalse($config->isActive());
    }

    public function testFluentInterface(): void
    {
        $config = new SinaWeiboOAuth2Config();
        
        $result = $config->setAppId('test')
            ->setAppSecret('secret')
            ->setScope('scope')
            ->setIsActive(true);

        $this->assertSame($config, $result);
    }

    public function testDefaultValues(): void
    {
        $config = new SinaWeiboOAuth2Config();
        
        $this->assertTrue($config->isActive());
        $this->assertNull($config->getScope());
    }

    public function testNullScope(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setScope(null);
        
        $this->assertNull($config->getScope());
    }
}