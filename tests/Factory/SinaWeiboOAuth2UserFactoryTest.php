<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Factory;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;
use Tourze\SinaWeiboOAuth2Bundle\Exception\InvalidUserDataException;
use Tourze\SinaWeiboOAuth2Bundle\Factory\SinaWeiboOAuth2UserFactory;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2UserFactory::class)]
final class SinaWeiboOAuth2UserFactoryTest extends TestCase
{
    private SinaWeiboOAuth2UserFactory $factory;

    private SinaWeiboOAuth2Config $config;

    public function testCreateFromDataWithMinimalData(): void
    {
        $data = [
            'uid' => '123456789',
            'access_token' => 'test_access_token',
            'expires_in' => 7200,
        ];

        $user = $this->factory->createFromData($data, $this->config);

        $this->assertInstanceOf(SinaWeiboOAuth2User::class, $user);
        $this->assertSame('123456789', $user->getUid());
        $this->assertSame('test_access_token', $user->getAccessToken());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getTokenExpireTime());
        $this->assertSame($this->config, $user->getConfig());
        $this->assertSame($data, $user->getRawData());
    }

    public function testCreateFromDataWithIdInsteadOfUid(): void
    {
        $data = [
            'id' => '987654321',
            'access_token' => 'test_access_token',
            'expires_in' => 3600,
        ];

        $user = $this->factory->createFromData($data, $this->config);

        $this->assertSame('987654321', $user->getUid());
        $this->assertSame('test_access_token', $user->getAccessToken());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getTokenExpireTime());
    }

    public function testCreateFromDataWithFullUserProfile(): void
    {
        $data = [
            'uid' => '123456789',
            'access_token' => 'test_access_token',
            'expires_in' => 7200,
            'name' => 'Test User',
            'screen_name' => 'testuser',
            'profile_image_url' => 'https://example.com/avatar.jpg',
            'avatar_large' => 'https://example.com/avatar_large.jpg',
            'gender' => 'm',
            'location' => 'Beijing',
            'description' => 'Test user description',
        ];

        $user = $this->factory->createFromData($data, $this->config);

        $this->assertSame('123456789', $user->getUid());
        $this->assertSame('test_access_token', $user->getAccessToken());
        $this->assertSame('testuser', $user->getNickname()); // screen_name takes precedence over name
        $this->assertSame('https://example.com/avatar_large.jpg', $user->getAvatar()); // avatar_large takes precedence
        $this->assertSame('m', $user->getGender());
        $this->assertSame('Beijing', $user->getLocation());
        $this->assertSame('Test user description', $user->getDescription());
        $this->assertSame($data, $user->getRawData());
    }

    public function testCreateFromDataWithNameFallback(): void
    {
        $data = [
            'uid' => '123456789',
            'access_token' => 'test_access_token',
            'expires_in' => 7200,
            'name' => 'Test User', // Only name, no screen_name
            'profile_image_url' => 'https://example.com/avatar.jpg', // Only profile_image_url, no avatar_large
        ];

        $user = $this->factory->createFromData($data, $this->config);

        $this->assertSame('Test User', $user->getNickname());
        $this->assertSame('https://example.com/avatar.jpg', $user->getAvatar());
    }

    public function testCreateFromDataWithDefaultExpiresIn(): void
    {
        $data = [
            'uid' => '123456789',
            'access_token' => 'test_access_token',
            // No expires_in provided
        ];

        $user = $this->factory->createFromData($data, $this->config);

        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getTokenExpireTime()); // Default value
    }

    public function testCreateFromDataThrowsExceptionWhenUidMissing(): void
    {
        $data = [
            'access_token' => 'test_access_token',
            'expires_in' => 7200,
        ];

        $this->expectException(InvalidUserDataException::class);
        $this->expectExceptionMessage('User data must contain uid or id field');

        $this->factory->createFromData($data, $this->config);
    }

    public function testCreateFromDataThrowsExceptionWhenUidEmpty(): void
    {
        $data = [
            'uid' => '',
            'access_token' => 'test_access_token',
            'expires_in' => 7200,
        ];

        $this->expectException(InvalidUserDataException::class);
        $this->expectExceptionMessage('User data must contain uid or id field');

        $this->factory->createFromData($data, $this->config);
    }

    public function testCreateFromDataThrowsExceptionWhenIdEmpty(): void
    {
        $data = [
            'id' => null,
            'access_token' => 'test_access_token',
            'expires_in' => 7200,
        ];

        $this->expectException(InvalidUserDataException::class);
        $this->expectExceptionMessage('User data must contain uid or id field');

        $this->factory->createFromData($data, $this->config);
    }

    public function testUpdateFromDataUpdatesAccessToken(): void
    {
        // Create initial user
        $initialData = [
            'uid' => '123456789',
            'access_token' => 'old_token',
            'expires_in' => 3600,
        ];
        $user = $this->factory->createFromData($initialData, $this->config);

        // Update user data
        $updateData = [
            'access_token' => 'new_token',
            'expires_in' => 7200,
            'refresh_token' => 'refresh_token_123',
        ];

        $updatedUser = $this->factory->updateFromData($user, $updateData);

        $this->assertSame($user, $updatedUser); // Same instance
        $this->assertSame('new_token', $user->getAccessToken());
        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getTokenExpireTime());
        $this->assertSame('refresh_token_123', $user->getRefreshToken());
        $this->assertSame($updateData, $user->getRawData());
    }

    public function testUpdateFromDataUpdatesProfile(): void
    {
        // Create initial user
        $initialData = [
            'uid' => '123456789',
            'access_token' => 'test_token',
            'expires_in' => 3600,
        ];
        $user = $this->factory->createFromData($initialData, $this->config);

        // Update user profile
        $updateData = [
            'access_token' => 'test_token',
            'name' => 'Updated Name',
            'screen_name' => 'updated_user',
            'profile_image_url' => 'https://example.com/new_avatar.jpg',
            'gender' => 'f',
            'location' => 'Shanghai',
            'description' => 'Updated description',
        ];

        $updatedUser = $this->factory->updateFromData($user, $updateData);

        $this->assertSame($user, $updatedUser);
        $this->assertSame('updated_user', $user->getNickname());
        $this->assertSame('https://example.com/new_avatar.jpg', $user->getAvatar());
        $this->assertSame('f', $user->getGender());
        $this->assertSame('Shanghai', $user->getLocation());
        $this->assertSame('Updated description', $user->getDescription());
    }

    public function testUpdateFromDataWithoutExpiresIn(): void
    {
        $initialData = [
            'uid' => '123456789',
            'access_token' => 'old_token',
            'expires_in' => 3600,
        ];
        $user = $this->factory->createFromData($initialData, $this->config);
        $originalExpireTime = $user->getTokenExpireTime();

        $updateData = [
            'access_token' => 'new_token',
            // No expires_in provided
        ];

        $this->factory->updateFromData($user, $updateData);

        $this->assertSame('new_token', $user->getAccessToken());
        $this->assertEquals($originalExpireTime, $user->getTokenExpireTime()); // Should remain unchanged
    }

    public function testUpdateFromDataWithoutRefreshToken(): void
    {
        $initialData = [
            'uid' => '123456789',
            'access_token' => 'old_token',
            'expires_in' => 3600,
        ];
        $user = $this->factory->createFromData($initialData, $this->config);
        $user->setRefreshToken('old_refresh_token');

        $updateData = [
            'access_token' => 'new_token',
            // No refresh_token provided
        ];

        $this->factory->updateFromData($user, $updateData);

        $this->assertSame('new_token', $user->getAccessToken());
        $this->assertSame('old_refresh_token', $user->getRefreshToken()); // Should remain unchanged
    }

    public function testCreateFromDataHandlesStringExpiresIn(): void
    {
        $data = [
            'uid' => '123456789',
            'access_token' => 'test_access_token',
            'expires_in' => '7200', // String instead of int
        ];

        $user = $this->factory->createFromData($data, $this->config);

        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getTokenExpireTime()); // Should be converted to int
    }

    public function testUpdateFromDataHandlesStringExpiresIn(): void
    {
        $initialData = [
            'uid' => '123456789',
            'access_token' => 'old_token',
            'expires_in' => 3600,
        ];
        $user = $this->factory->createFromData($initialData, $this->config);

        $updateData = [
            'access_token' => 'new_token',
            'expires_in' => '7200', // String instead of int
        ];

        $this->factory->updateFromData($user, $updateData);

        $this->assertInstanceOf(\DateTimeImmutable::class, $user->getTokenExpireTime()); // Should be converted to int
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = new SinaWeiboOAuth2UserFactory();
        $this->config = new SinaWeiboOAuth2Config();
        $this->config->setAppId('test_app_id');
        $this->config->setAppSecret('test_app_secret');
    }
}
