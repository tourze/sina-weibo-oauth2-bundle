<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Unit\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;

class SinaWeiboOAuth2UserTest extends TestCase
{
    private SinaWeiboOAuth2Config $config;

    public function testConstructor(): void
    {
        $user = new SinaWeiboOAuth2User('test_uid', 'test_token', 3600, $this->config);

        $this->assertEquals('test_uid', $user->getUid());
        $this->assertEquals('test_token', $user->getAccessToken());
        $this->assertSame($this->config, $user->getConfig());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getTokenExpireTime());
        $this->assertNull($user->getRefreshToken());
    }

    public function testSettersAndGetters(): void
    {
        $user = new SinaWeiboOAuth2User('test_uid', 'test_token', 3600, $this->config);

        $user->setAccessToken('new_token')
            ->setRefreshToken('refresh_token')
            ->setNickname('Test User')
            ->setAvatar('http://example.com/avatar.jpg')
            ->setGender('m')
            ->setLocation('Beijing')
            ->setDescription('Test description')
            ->setRawData(['test' => 'data']);

        $this->assertEquals('new_token', $user->getAccessToken());
        $this->assertEquals('refresh_token', $user->getRefreshToken());
        $this->assertEquals('Test User', $user->getNickname());
        $this->assertEquals('http://example.com/avatar.jpg', $user->getAvatar());
        $this->assertEquals('m', $user->getGender());
        $this->assertEquals('Beijing', $user->getLocation());
        $this->assertEquals('Test description', $user->getDescription());
        $this->assertEquals(['test' => 'data'], $user->getRawData());
    }

    public function testSetExpiresIn(): void
    {
        $user = new SinaWeiboOAuth2User('test_uid', 'test_token', 3600, $this->config);

        $beforeUpdate = new \DateTimeImmutable('+7200 seconds');
        $user->setExpiresIn(7200);
        $afterUpdate = new \DateTimeImmutable('+7200 seconds');

        $this->assertGreaterThanOrEqual($beforeUpdate, $user->getTokenExpireTime());
        $this->assertLessThanOrEqual($afterUpdate, $user->getTokenExpireTime());
    }

    public function testIsTokenExpired(): void
    {
        $user = new SinaWeiboOAuth2User('test_uid', 'test_token', -1, $this->config);

        $this->assertTrue($user->isTokenExpired());

        $user->setExpiresIn(3600);
        $this->assertFalse($user->isTokenExpired());
    }

    public function testFluentInterface(): void
    {
        $user = new SinaWeiboOAuth2User('test_uid', 'test_token', 3600, $this->config);

        $result = $user->setNickname('Test')
            ->setAvatar('avatar.jpg')
            ->setGender('f')
            ->setLocation('Shanghai');

        $this->assertSame($user, $result);
    }

    public function testDefaultValues(): void
    {
        $user = new SinaWeiboOAuth2User('test_uid', 'test_token', 3600, $this->config);

        $this->assertNull($user->getNickname());
        $this->assertNull($user->getAvatar());
        $this->assertNull($user->getGender());
        $this->assertNull($user->getLocation());
        $this->assertNull($user->getDescription());
        $this->assertNull($user->getRawData());
        $this->assertNull($user->getRefreshToken());
    }

    public function testTimestampTrait(): void
    {
        $user = new SinaWeiboOAuth2User('test_uid', 'test_token', 3600, $this->config);

        // Timestamps are null until persisted
        $this->assertNull($user->getCreatedAt());
        $this->assertNull($user->getUpdatedAt());
    }

    protected function setUp(): void
    {
        $this->config = new SinaWeiboOAuth2Config();
        $this->config->setAppId('test_app_id')
            ->setAppSecret('test_secret');
    }
}