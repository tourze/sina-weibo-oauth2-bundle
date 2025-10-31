<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2User::class)]
final class SinaWeiboOAuth2UserTest extends AbstractEntityTestCase
{
    private SinaWeiboOAuth2Config $config;

    public function testConstructorSetsRequiredProperties(): void
    {
        $uid = 'test_uid_12345';
        $accessToken = 'test_access_token';
        $expiresIn = 3600;

        $user = $this->createUserWithData($uid, $accessToken, $expiresIn, $this->config);

        $this->assertNull($user->getId());
        $this->assertSame($uid, $user->getUid());
        $this->assertSame($accessToken, $user->getAccessToken());
        $this->assertSame($this->config, $user->getConfig());
        $this->assertNull($user->getRefreshToken());
        $this->assertNull($user->getNickname());
        $this->assertNull($user->getAvatar());
        $this->assertNull($user->getGender());
        $this->assertNull($user->getLocation());
        $this->assertNull($user->getDescription());
        $this->assertNull($user->getRawData());
    }

    public function testConstructorSetsTokenExpireTimeCorrectlyForPositiveSeconds(): void
    {
        $user = $this->createUserWithData('test_uid', 'token', 3600, $this->config);

        $expireTime = $user->getTokenExpireTime();
        $now = new \DateTimeImmutable();

        $this->assertInstanceOf(\DateTimeImmutable::class, $expireTime);
        $this->assertGreaterThan($now, $expireTime);

        $expectedTime = $now->modify('+3600 seconds');
        $timeDiff = abs($expireTime->getTimestamp() - $expectedTime->getTimestamp());
        $this->assertLessThan(2, $timeDiff);
    }

    public function testConstructorSetsTokenExpireTimeCorrectlyForNegativeSeconds(): void
    {
        $user = $this->createUserWithData('test_uid', 'token', -600, $this->config);

        $expireTime = $user->getTokenExpireTime();
        $now = new \DateTimeImmutable();

        $this->assertInstanceOf(\DateTimeImmutable::class, $expireTime);
        $this->assertLessThan($now, $expireTime);

        $expectedTime = $now->modify('-600 seconds');
        $timeDiff = abs($expireTime->getTimestamp() - $expectedTime->getTimestamp());
        $this->assertLessThan(2, $timeDiff);
    }

    public function testConstructorSetsTokenExpireTimeCorrectlyForZeroSeconds(): void
    {
        $user = $this->createUserWithData('test_uid', 'token', 0, $this->config);

        $expireTime = $user->getTokenExpireTime();
        $now = new \DateTimeImmutable();

        $timeDiff = abs($expireTime->getTimestamp() - $now->getTimestamp());
        $this->assertLessThan(2, $timeDiff);
    }

    public function testGetUid(): void
    {
        $uid = 'test_uid_value';
        $user = $this->createUserWithData($uid, 'token', 3600, $this->config);

        $this->assertSame($uid, $user->getUid());
    }

    public function testSetAndGetAccessToken(): void
    {
        $user = $this->createUserWithData('uid', 'initial_token', 3600, $this->config);
        $newToken = 'new_access_token';

        $user->setAccessToken($newToken);

        $this->assertSame($newToken, $user->getAccessToken());
    }

    public function testSetExpiresInWithPositiveValue(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);
        $newExpiresIn = 7200;

        $user->setExpiresIn($newExpiresIn);

        $expireTime = $user->getTokenExpireTime();
        $now = new \DateTimeImmutable();
        $expectedTime = $now->modify('+7200 seconds');
        $timeDiff = abs($expireTime->getTimestamp() - $expectedTime->getTimestamp());
        $this->assertLessThan(2, $timeDiff);
    }

    public function testSetExpiresInWithNegativeValue(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);
        $newExpiresIn = -1800;

        $user->setExpiresIn($newExpiresIn);

        $expireTime = $user->getTokenExpireTime();
        $now = new \DateTimeImmutable();
        $expectedTime = $now->modify('-1800 seconds');
        $timeDiff = abs($expireTime->getTimestamp() - $expectedTime->getTimestamp());
        $this->assertLessThan(2, $timeDiff);
    }

    public function testSetAndGetRefreshToken(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);
        $refreshToken = 'refresh_token_12345';

        $this->assertNull($user->getRefreshToken());

        $user->setRefreshToken($refreshToken);

        $this->assertSame($refreshToken, $user->getRefreshToken());
    }

    public function testSetRefreshTokenToNull(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);
        $user->setRefreshToken('initial_refresh');

        $user->setRefreshToken(null);

        $this->assertNull($user->getRefreshToken());
    }

    public function testGetConfig(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);

        $this->assertSame($this->config, $user->getConfig());
    }

    public function testSetAndGetNickname(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);
        $nickname = 'Test User';

        $this->assertNull($user->getNickname());

        $user->setNickname($nickname);

        $this->assertSame($nickname, $user->getNickname());
    }

    public function testSetNicknameToNull(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);
        $user->setNickname('Initial Name');

        $user->setNickname(null);

        $this->assertNull($user->getNickname());
    }

    public function testSetAndGetAvatar(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);
        $avatar = 'https://example.com/avatar.jpg';

        $this->assertNull($user->getAvatar());

        $user->setAvatar($avatar);

        $this->assertSame($avatar, $user->getAvatar());
    }

    public function testSetAvatarToNull(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);
        $user->setAvatar('https://example.com/initial.jpg');

        $user->setAvatar(null);

        $this->assertNull($user->getAvatar());
    }

    public function testSetAndGetGender(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);
        $gender = 'female';

        $this->assertNull($user->getGender());

        $user->setGender($gender);

        $this->assertSame($gender, $user->getGender());
    }

    public function testSetAndGetLocation(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);
        $location = 'Shanghai';

        $this->assertNull($user->getLocation());

        $user->setLocation($location);

        $this->assertSame($location, $user->getLocation());
    }

    public function testSetAndGetDescription(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);
        $description = 'This is a test user description';

        $this->assertNull($user->getDescription());

        $user->setDescription($description);

        $this->assertSame($description, $user->getDescription());
    }

    public function testSetAndGetRawData(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);
        $rawData = [
            'id' => 123456789,
            'name' => 'Test User',
            'screen_name' => 'test_user',
            'followers_count' => 100,
        ];

        $this->assertNull($user->getRawData());

        $user->setRawData($rawData);

        $this->assertSame($rawData, $user->getRawData());
    }

    public function testSetRawDataToNull(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);
        $user->setRawData(['initial' => 'data']);

        $user->setRawData(null);

        $this->assertNull($user->getRawData());
    }

    public function testIsTokenExpiredReturnsFalseForFutureExpireTime(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);

        $this->assertFalse($user->isTokenExpired());
    }

    public function testIsTokenExpiredReturnsTrueForPastExpireTime(): void
    {
        $user = $this->createUserWithData('uid', 'token', -1, $this->config);

        $this->assertTrue($user->isTokenExpired());
    }

    public function testIsTokenExpiredReturnsTrueForCurrentTime(): void
    {
        $user = $this->createUserWithData('uid', 'token', 0, $this->config);

        sleep(1);

        $this->assertTrue($user->isTokenExpired());
    }

    public function testToStringWithNickname(): void
    {
        $user = $this->createUserWithData('test_uid', 'token', 3600, $this->config);
        $user->setNickname('Test User');

        $result = $user->__toString();

        $this->assertSame('SinaWeiboOAuth2User[test_uid:Test User]', $result);
    }

    public function testToStringWithoutNickname(): void
    {
        $user = $this->createUserWithData('test_uid', 'token', 3600, $this->config);

        $result = $user->__toString();

        $this->assertSame('SinaWeiboOAuth2User[test_uid:unknown]', $result);
    }

    public function testStringableInterface(): void
    {
        $user = $this->createUserWithData('stringable_uid', 'token', 3600, $this->config);
        $user->setNickname('Stringable User');

        $stringRepresentation = (string) $user;

        $this->assertSame('SinaWeiboOAuth2User[stringable_uid:Stringable User]', $stringRepresentation);
    }

    public function testGetUpdatedAtReturnsUpdateTime(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);

        $updatedAt = $user->getUpdatedAt();
        $updateTime = $user->getUpdateTime();

        $this->assertSame($updateTime, $updatedAt);
    }

    public function testFluentInterface(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);

        $user->setAccessToken('fluent_token');
        $user->setRefreshToken('fluent_refresh');
        $user->setNickname('Fluent User');
        $user->setAvatar('https://example.com/fluent.jpg');
        $user->setGender('male');
        $user->setLocation('Beijing');
        $user->setDescription('Fluent description');

        $this->assertSame('fluent_token', $user->getAccessToken());
        $this->assertSame('fluent_refresh', $user->getRefreshToken());
        $this->assertSame('Fluent User', $user->getNickname());
        $this->assertSame('https://example.com/fluent.jpg', $user->getAvatar());
        $this->assertSame('male', $user->getGender());
        $this->assertSame('Beijing', $user->getLocation());
        $this->assertSame('Fluent description', $user->getDescription());
    }

    public function testEntityImplementsStringableCorrectly(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);

        $this->assertInstanceOf(\Stringable::class, $user);
    }

    public function testGetTokenExpireTimeIsImmutable(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);

        $expireTime1 = $user->getTokenExpireTime();
        $expireTime2 = $user->getTokenExpireTime();

        $this->assertSame($expireTime1, $expireTime2);
        $this->assertInstanceOf(\DateTimeImmutable::class, $expireTime1);
    }

    public function testTokenExpirationChangesOverTime(): void
    {
        $user = $this->createUserWithData('uid', 'token', 1, $this->config);

        $this->assertFalse($user->isTokenExpired());

        sleep(2);

        $this->assertTrue($user->isTokenExpired());
    }

    public function testUserWithEmptyUid(): void
    {
        $user = $this->createUserWithData('', 'token', 3600, $this->config);

        $this->assertSame('', $user->getUid());
        $this->assertSame('SinaWeiboOAuth2User[:unknown]', $user->__toString());
    }

    public function testUserWithEmptyNickname(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);
        $user->setNickname('');

        $this->assertSame('', $user->getNickname());
        $this->assertSame('SinaWeiboOAuth2User[uid:]', $user->__toString());
    }

    public function testComplexRawDataHandling(): void
    {
        $user = $this->createUserWithData('uid', 'token', 3600, $this->config);
        $complexRawData = [
            'user' => [
                'basic_info' => [
                    'id' => 123456789,
                    'name' => 'Complex User',
                ],
                'counts' => [
                    'followers' => 1000,
                    'following' => 500,
                ],
            ],
            'permissions' => ['read', 'write'],
            'metadata' => null,
        ];

        $user->setRawData($complexRawData);

        $this->assertSame($complexRawData, $user->getRawData());
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
        return $this->createUserWithData('test_uid', 'test_token', 3600, $this->config);
    }

    private function createUserWithData(string $uid, string $accessToken, int $expiresIn, SinaWeiboOAuth2Config $config): SinaWeiboOAuth2User
    {
        $user = new SinaWeiboOAuth2User();
        $user->setUid($uid);
        $user->setAccessToken($accessToken);
        $user->setExpiresIn($expiresIn);
        $user->setConfig($config);

        return $user;
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        // 注意：SinaWeiboOAuth2User 有构造函数参数，所以这里只测试有 setter 的属性
        yield 'accessToken' => ['accessToken', 'new_token'];
        yield 'refreshToken' => ['refreshToken', 'refresh_token'];
        yield 'nickname' => ['nickname', 'Test User'];
        yield 'avatar' => ['avatar', 'https://example.com/avatar.jpg'];
        yield 'gender' => ['gender', 'male'];
        yield 'location' => ['location', 'Shanghai'];
        yield 'description' => ['description', 'Test description'];
        yield 'rawData' => ['rawData', ['key' => 'value']];
    }
}
