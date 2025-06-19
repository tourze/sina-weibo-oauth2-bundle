<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Entity;

use PHPUnit\Framework\TestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;

class SinaWeiboOAuth2StateTest extends TestCase
{
    private SinaWeiboOAuth2Config $config;

    public function testConstructor(): void
    {
        $state = new SinaWeiboOAuth2State('test_state', $this->config);

        $this->assertEquals('test_state', $state->getState());
        $this->assertSame($this->config, $state->getConfig());
        $this->assertFalse($state->isUsed());
        $this->assertInstanceOf(\DateTimeImmutable::class, $state->getExpireTime());
        $this->assertNull($state->getSessionId());
    }

    public function testCustomExpirationTime(): void
    {
        $beforeCreation = new \DateTimeImmutable('+5 minutes');
        $state = new SinaWeiboOAuth2State('test_state', $this->config, 5);
        $afterCreation = new \DateTimeImmutable('+5 minutes');

        $this->assertGreaterThanOrEqual($beforeCreation, $state->getExpireTime());
        $this->assertLessThanOrEqual($afterCreation, $state->getExpireTime());
    }

    public function testSessionId(): void
    {
        $state = new SinaWeiboOAuth2State('test_state', $this->config);
        $state->setSessionId('test_session_id');

        $this->assertEquals('test_session_id', $state->getSessionId());
    }

    public function testMarkAsUsed(): void
    {
        $state = new SinaWeiboOAuth2State('test_state', $this->config);

        $this->assertFalse($state->isUsed());

        $result = $state->markAsUsed();

        $this->assertTrue($state->isUsed());
        $this->assertSame($state, $result);
    }

    public function testIsValid(): void
    {
        $state = new SinaWeiboOAuth2State('test_state', $this->config);

        $this->assertTrue($state->isValid());

        $state->markAsUsed();
        $this->assertFalse($state->isValid());
    }

    public function testIsExpired(): void
    {
        $state = new SinaWeiboOAuth2State('test_state', $this->config, -1);

        $this->assertTrue($state->isExpired());
        $this->assertFalse($state->isValid());
    }

    public function testValidState(): void
    {
        $state = new SinaWeiboOAuth2State('test_state', $this->config, 10);

        $this->assertFalse($state->isExpired());
        $this->assertFalse($state->isUsed());
        $this->assertTrue($state->isValid());
    }

    public function testTimestampTrait(): void
    {
        $state = new SinaWeiboOAuth2State('test_state', $this->config);

        // Timestamps are null until persisted
        $this->assertNull($state->getCreatedAt());
        $this->assertNull($state->getUpdatedAt());
    }

    protected function setUp(): void
    {
        $this->config = new SinaWeiboOAuth2Config();
        $this->config->setAppId('test_app_id')
            ->setAppSecret('test_secret');
    }
}