<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;
use Tourze\SinaWeiboOAuth2Bundle\DependencyInjection\SinaWeiboOAuth2Extension;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2Extension::class)]
final class SinaWeiboOAuth2ExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    private SinaWeiboOAuth2Extension $extension;

    private ContainerBuilder $container;

    public function testLoadWithProductionEnvironment(): void
    {
        $this->container->setParameter('kernel.environment', 'prod');

        $this->extension->load([], $this->container);

        // Check that services are loaded via autowiring
        $this->assertTrue($this->container->hasDefinition('Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service'));
        $this->assertTrue($this->container->hasDefinition('Tourze\SinaWeiboOAuth2Bundle\Factory\SinaWeiboOAuth2UserFactory'));
    }

    public function testLoadWithDevelopmentEnvironment(): void
    {
        $this->container->setParameter('kernel.environment', 'dev');

        $this->extension->load([], $this->container);

        // Check that services are loaded via autowiring
        $this->assertTrue($this->container->hasDefinition('Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service'));
        $this->assertTrue($this->container->hasDefinition('Tourze\SinaWeiboOAuth2Bundle\Factory\SinaWeiboOAuth2UserFactory'));
    }

    public function testLoadWithTestEnvironment(): void
    {
        $this->container->setParameter('kernel.environment', 'test');

        $this->extension->load([], $this->container);

        // Check that services are loaded via autowiring
        $this->assertTrue($this->container->hasDefinition('Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service'));
        $this->assertTrue($this->container->hasDefinition('Tourze\SinaWeiboOAuth2Bundle\Factory\SinaWeiboOAuth2UserFactory'));
    }

    public function testLoadWithoutEnvironmentParameterDefaultsToProd(): void
    {
        $this->extension->load([], $this->container);

        // Check that services are loaded via autowiring
        $this->assertTrue($this->container->hasDefinition('Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service'));
        $this->assertTrue($this->container->hasDefinition('Tourze\SinaWeiboOAuth2Bundle\Factory\SinaWeiboOAuth2UserFactory'));
    }

    public function testLoadLoadsBaseServices(): void
    {
        $this->extension->load([], $this->container);

        $expectedServices = [
            'Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service',
            'Tourze\SinaWeiboOAuth2Bundle\Factory\SinaWeiboOAuth2UserFactory',
            'Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2ConfigRepository',
            'Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2StateRepository',
            'Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2UserRepository',
            'Tourze\SinaWeiboOAuth2Bundle\Service\AttributeControllerLoader',
        ];

        foreach ($expectedServices as $serviceId) {
            $this->assertTrue(
                $this->container->hasDefinition($serviceId),
                sprintf('Service "%s" should be defined', $serviceId)
            );
        }
    }

    public function testLoadWithMultipleConfigs(): void
    {
        $configs = [
            [],
            ['some_config' => 'value'],
        ];

        $this->extension->load($configs, $this->container);

        $this->assertTrue($this->container->hasDefinition('Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service'));
    }

    public function testExtensionInheritsFromBaseClass(): void
    {
        $this->assertInstanceOf(Extension::class, $this->extension);
    }

    public function testExtensionUsesCorrectFileLocator(): void
    {
        $reflection = new \ReflectionClass($this->extension);
        $method = $reflection->getMethod('load');

        $this->assertTrue($method->isPublic());
        $parameters = $method->getParameters();
        $this->assertSame('configs', $parameters[0]->getName());
        $this->assertSame('container', $parameters[1]->getName());
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->extension = new SinaWeiboOAuth2Extension();
        $this->container = new ContainerBuilder();
        $this->container->setParameter('kernel.environment', 'test');
    }
}
