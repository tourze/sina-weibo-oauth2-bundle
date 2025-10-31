<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2State::class)]
final class SinaWeiboOAuth2StateTest extends AbstractEntityTestCase
{
    private SinaWeiboOAuth2Config $config;

    public function testConstructorSetsRequiredProperties(): void
    {
        $state = 'test_state_12345';
        $expiresInMinutes = 15;

        $entity = $this->createStateWithData($state, $this->config, $expiresInMinutes);

        $this->assertNull($entity->getId());
        $this->assertSame($state, $entity->getState());
        $this->assertSame($this->config, $entity->getConfig());
        $this->assertFalse($entity->isUsed());
        $this->assertNull($entity->getSessionId());
    }

    public function testConstructorSetsExpireTimeCorrectlyForPositiveMinutes(): void
    {
        $state = $this->createStateWithData('test_state', $this->config, 30);

        $expireTime = $state->getExpireTime();
        $now = new \DateTimeImmutable();

        $this->assertInstanceOf(\DateTimeImmutable::class, $expireTime);
        $this->assertGreaterThan($now, $expireTime);

        $expectedTime = $now->modify('+30 minutes');
        $timeDiff = abs($expireTime->getTimestamp() - $expectedTime->getTimestamp());
        $this->assertLessThan(2, $timeDiff);
    }

    public function testConstructorSetsExpireTimeCorrectlyForNegativeMinutes(): void
    {
        $state = $this->createStateWithData('test_state', $this->config, -10);

        $expireTime = $state->getExpireTime();
        $now = new \DateTimeImmutable();

        $this->assertInstanceOf(\DateTimeImmutable::class, $expireTime);
        $this->assertLessThan($now, $expireTime);

        $expectedTime = $now->modify('-10 minutes');
        $timeDiff = abs($expireTime->getTimestamp() - $expectedTime->getTimestamp());
        $this->assertLessThan(2, $timeDiff);
    }

    public function testConstructorSetsExpireTimeCorrectlyForZeroMinutes(): void
    {
        $state = $this->createStateWithData('test_state', $this->config, 0);

        $expireTime = $state->getExpireTime();
        $now = new \DateTimeImmutable();

        $timeDiff = abs($expireTime->getTimestamp() - $now->getTimestamp());
        $this->assertLessThan(2, $timeDiff);
    }

    public function testConstructorUsesDefaultExpireTime(): void
    {
        $state = $this->createStateWithData('test_state', $this->config);

        $expireTime = $state->getExpireTime();
        $now = new \DateTimeImmutable();

        $expectedTime = $now->modify('+10 minutes');
        $timeDiff = abs($expireTime->getTimestamp() - $expectedTime->getTimestamp());
        $this->assertLessThan(2, $timeDiff);
    }

    public function testGetState(): void
    {
        $stateValue = 'test_state_value';
        $state = $this->createStateWithData($stateValue, $this->config);

        $this->assertSame($stateValue, $state->getState());
    }

    public function testGetConfig(): void
    {
        $state = $this->createStateWithData('test_state', $this->config);

        $this->assertSame($this->config, $state->getConfig());
    }

    public function testSetAndGetSessionId(): void
    {
        $state = $this->createStateWithData('test_state', $this->config);
        $sessionId = 'session_id_12345';

        $this->assertNull($state->getSessionId());

        $state->setSessionId($sessionId);

        $this->assertSame($sessionId, $state->getSessionId());
    }

    public function testSetSessionIdToNull(): void
    {
        $state = $this->createStateWithData('test_state', $this->config);
        $state->setSessionId('initial_session');

        $state->setSessionId(null);

        $this->assertNull($state->getSessionId());
    }

    public function testIsUsedDefaultsToFalse(): void
    {
        $state = $this->createStateWithData('test_state', $this->config);

        $this->assertFalse($state->isUsed());
    }

    public function testMarkAsUsed(): void
    {
        $state = $this->createStateWithData('test_state', $this->config);

        $this->assertFalse($state->isUsed());

        $state->markAsUsed();

        $this->assertTrue($state->isUsed());
    }

    public function testIsValidReturnsTrueForUnusedNonExpiredState(): void
    {
        $state = $this->createStateWithData('test_state', $this->config, 30);

        $this->assertTrue($state->isValid());
    }

    public function testIsValidReturnsFalseForUsedState(): void
    {
        $state = $this->createStateWithData('test_state', $this->config, 30);
        $state->markAsUsed();

        $this->assertFalse($state->isValid());
    }

    public function testIsValidReturnsFalseForExpiredState(): void
    {
        $state = $this->createStateWithData('test_state', $this->config, -1);

        $this->assertFalse($state->isValid());
    }

    public function testIsExpiredReturnsFalseForFutureExpireTime(): void
    {
        $state = $this->createStateWithData('test_state', $this->config, 30);

        $this->assertFalse($state->isExpired());
    }

    public function testIsExpiredReturnsTrueForPastExpireTime(): void
    {
        $state = $this->createStateWithData('test_state', $this->config, -1);

        $this->assertTrue($state->isExpired());
    }

    public function testIsExpiredReturnsTrueForCurrentTime(): void
    {
        $state = $this->createStateWithData('test_state', $this->config, 0);

        sleep(1);

        $this->assertTrue($state->isExpired());
    }

    public function testToStringWithStateValue(): void
    {
        $stateValue = 'test_state_12345';
        $state = $this->createStateWithData($stateValue, $this->config);

        $result = $state->__toString();

        $this->assertSame('SinaWeiboOAuth2State[test_state_12345]', $result);
    }

    public function testStringableInterface(): void
    {
        $state = $this->createStateWithData('stringable_test', $this->config);

        $stringRepresentation = (string) $state;

        $this->assertSame('SinaWeiboOAuth2State[stringable_test]', $stringRepresentation);
    }

    public function testGetUpdatedAtReturnsUpdateTime(): void
    {
        $state = $this->createStateWithData('test_state', $this->config);

        $updatedAt = $state->getUpdatedAt();
        $updateTime = $state->getUpdateTime();

        $this->assertSame($updateTime, $updatedAt);
    }

    public function testFluentInterface(): void
    {
        $state = $this->createStateWithData('test_state', $this->config);

        $state->setSessionId('fluent_session');
        $state->markAsUsed();

        $this->assertSame('fluent_session', $state->getSessionId());
        $this->assertTrue($state->isUsed());
    }

    public function testEntityImplementsStringableCorrectly(): void
    {
        $state = $this->createStateWithData('test_state', $this->config);

        $this->assertInstanceOf(\Stringable::class, $state);
    }

    public function testStateValidityChangesOverTime(): void
    {
        $state = $this->createStateWithData('test_state', $this->config, 1);

        $this->assertTrue($state->isValid());
        $this->assertFalse($state->isExpired());

        sleep(62);

        $this->assertFalse($state->isValid());
        $this->assertTrue($state->isExpired());
    }

    public function testStateWithEmptyString(): void
    {
        $state = $this->createStateWithData('', $this->config);

        $this->assertSame('', $state->getState());
        $this->assertSame('SinaWeiboOAuth2State[]', $state->__toString());
    }

    public function testGetExpireTimeIsImmutable(): void
    {
        $state = $this->createStateWithData('test_state', $this->config, 30);

        $expireTime1 = $state->getExpireTime();
        $expireTime2 = $state->getExpireTime();

        $this->assertSame($expireTime1, $expireTime2);
        $this->assertInstanceOf(\DateTimeImmutable::class, $expireTime1);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->config = new SinaWeiboOAuth2Config();
        $this->config->setAppId('test_app_id');
        $this->config->setAppSecret('test_secret');
        $this->config->setScope(null);
        $this->config->setValid(true);
    }

    protected function createEntity(): object
    {
        return $this->createStateWithData('test_state', $this->config, 10);
    }

    private function createStateWithData(string $state, SinaWeiboOAuth2Config $config, int $expiresInMinutes = 10): SinaWeiboOAuth2State
    {
        $stateEntity = new SinaWeiboOAuth2State();
        $stateEntity->setState($state);
        $stateEntity->setConfig($config);
        $stateEntity->setExpiresInMinutes($expiresInMinutes);

        return $stateEntity;
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        // 注意：SinaWeiboOAuth2State 有构造函数参数，所以这里只测试有 setter 的属性
        yield 'sessionId' => ['sessionId', 'test_session_id'];
    }
}
