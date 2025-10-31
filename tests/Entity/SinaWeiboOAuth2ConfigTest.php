<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2Config::class)]
final class SinaWeiboOAuth2ConfigTest extends AbstractEntityTestCase
{
    public function testConstructorSetsDefaults(): void
    {
        $config = new SinaWeiboOAuth2Config();

        $this->assertNull($config->getId());
        $this->assertTrue($config->isValid());
        $this->assertNull($config->getScope());
    }

    public function testSetAndGetAppId(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $appId = 'test_app_id_12345';

        $config->setAppId($appId);

        $this->assertSame($appId, $config->getAppId());
    }

    public function testSetAndGetAppSecret(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $appSecret = 'test_app_secret_67890';

        $config->setAppSecret($appSecret);

        $this->assertSame($appSecret, $config->getAppSecret());
    }

    public function testSetAndGetScope(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $scope = 'email,user_show,statuses_read';

        $config->setScope($scope);

        $this->assertSame($scope, $config->getScope());
    }

    public function testSetScopeToNull(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setScope('initial_scope');

        $config->setScope(null);

        $this->assertNull($config->getScope());
    }

    public function testSetAndGetValid(): void
    {
        $config = new SinaWeiboOAuth2Config();

        $this->assertTrue($config->isValid());

        $config->setValid(false);

        $this->assertFalse($config->isValid());

        $config->setValid(true);
        $this->assertTrue($config->isValid());
    }

    public function testIsActiveMethodIsDeprecatedButWorks(): void
    {
        $config = new SinaWeiboOAuth2Config();

        $this->assertTrue($config->isValid());

        $config->setValid(false);
        $this->assertFalse($config->isValid());
    }

    public function testSetIsActiveMethodIsDeprecatedButWorks(): void
    {
        $config = new SinaWeiboOAuth2Config();

        $config->setValid(false);

        $this->assertFalse($config->isValid());
        $this->assertFalse($config->isValid());

        $config->setValid(true);
        $this->assertTrue($config->isValid());
        $this->assertTrue($config->isValid());
    }

    public function testToStringWithAppId(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_123');

        $result = $config->__toString();

        $this->assertSame('SinaWeiboOAuth2Config[test_app_123]', $result);
    }

    public function testToStringWithoutAppId(): void
    {
        $config = new SinaWeiboOAuth2Config();

        $result = $config->__toString();

        $this->assertSame('SinaWeiboOAuth2Config[]', $result);
    }

    public function testFluentInterface(): void
    {
        $config = new SinaWeiboOAuth2Config();

        $config->setAppId('fluent_app_id');
        $config->setAppSecret('fluent_secret');
        $config->setScope('fluent_scope');
        $config->setValid(false);

        $this->assertSame('fluent_app_id', $config->getAppId());
        $this->assertSame('fluent_secret', $config->getAppSecret());
        $this->assertSame('fluent_scope', $config->getScope());
        $this->assertFalse($config->isValid());
    }

    public function testStringableInterface(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('stringable_test');

        $stringRepresentation = (string) $config;

        $this->assertSame('SinaWeiboOAuth2Config[stringable_test]', $stringRepresentation);
    }

    public function testValidationConstraintsAreApplied(): void
    {
        $config = new SinaWeiboOAuth2Config();

        $this->expectNotToPerformAssertions();

        $config->setAppId('valid_app_id');
        $config->setAppSecret('valid_app_secret');
        $config->setScope('valid_scope');
        $config->setValid(true);
    }

    public function testEmptyAppIdCanBeSet(): void
    {
        $config = new SinaWeiboOAuth2Config();

        $config->setAppId('');

        $this->assertSame('', $config->getAppId());
    }

    public function testEmptyAppSecretCanBeSet(): void
    {
        $config = new SinaWeiboOAuth2Config();

        $config->setAppSecret('');

        $this->assertSame('', $config->getAppSecret());
    }

    public function testLongScopeCanBeSet(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $longScope = str_repeat('scope_', 100);

        $config->setScope($longScope);

        $this->assertSame($longScope, $config->getScope());
    }

    public function testEntityImplementsStringableCorrectly(): void
    {
        $config = new SinaWeiboOAuth2Config();

        $this->assertInstanceOf(\Stringable::class, $config);
    }

    protected function createEntity(): object
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_id');
        $config->setAppSecret('test_secret');
        $config->setScope('test_scope');
        $config->setValid(true);

        return $config;
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'appId' => ['appId', 'test_app_id'];
        yield 'appSecret' => ['appSecret', 'test_secret'];
        yield 'scope' => ['scope', 'email,user_show'];
        yield 'valid' => ['valid', true];
    }
}
