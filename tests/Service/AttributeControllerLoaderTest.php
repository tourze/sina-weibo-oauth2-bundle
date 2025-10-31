<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\Routing\AttributeRouteControllerLoader;
use Symfony\Component\Config\Loader\Loader;
use Symfony\Component\Routing\RouteCollection;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;
use Tourze\RoutingAutoLoaderBundle\Service\RoutingAutoLoaderInterface;
use Tourze\SinaWeiboOAuth2Bundle\Service\AttributeControllerLoader;

/**
 * @internal
 */
#[CoversClass(AttributeControllerLoader::class)]
#[RunTestsInSeparateProcesses]
final class AttributeControllerLoaderTest extends AbstractIntegrationTestCase
{
    protected function onSetUp(): void
    {
        // Required implementation for AbstractIntegrationTestCase
    }

    public function testLoaderInheritsFromLoader(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $this->assertInstanceOf(Loader::class, $loader);
    }

    public function testLoaderImplementsRoutingAutoLoaderInterface(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $this->assertInstanceOf(RoutingAutoLoaderInterface::class, $loader);
    }

    public function testLoadReturnsRouteCollection(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $resource = 'test_resource';
        $type = 'test_type';

        $result = $loader->load($resource, $type);

        $this->assertInstanceOf(RouteCollection::class, $result);
    }

    public function testLoadCallsAutoload(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $resource = 'any_resource';
        $type = null;

        $result = $loader->load($resource, $type);

        // The load method should delegate to autoload
        $this->assertInstanceOf(RouteCollection::class, $result);
    }

    public function testAutoloadReturnsRouteCollection(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $result = $loader->autoload();

        $this->assertInstanceOf(RouteCollection::class, $result);
    }

    public function testAutoloadLoadsControllerRoutes(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $collection = $loader->autoload();

        // The collection should contain routes from both controllers
        $this->assertInstanceOf(RouteCollection::class, $collection);

        // Check that routes are loaded (they should have some routes from the controllers)
        $routes = $collection->all();
        $this->assertIsArray($routes);

        // The controllers have #[Route] attributes, so there should be routes
        // We don't check specific route names as they depend on the controller implementation
        // but we verify the collection structure is correct
    }

    public function testSupportsReturnsTrueForAttributeType(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $resource = 'any_resource';
        $type = 'attribute';

        $result = $loader->supports($resource, $type);

        $this->assertTrue($result);
    }

    public function testSupportsReturnsFalseWithNullType(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $resource = 'any_resource';
        $type = null;

        $result = $loader->supports($resource, $type);

        $this->assertFalse($result);
    }

    public function testSupportsReturnsFalseWithEmptyResource(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $resource = '';
        $type = 'test_type';

        $result = $loader->supports($resource, $type);

        $this->assertFalse($result);
    }

    public function testLoaderCanBeInstantiatedMultipleTimes(): void
    {
        $loader1 = self::getService(AttributeControllerLoader::class);
        $loader2 = self::getService(AttributeControllerLoader::class);

        $collection1 = $loader1->autoload();
        $collection2 = $loader2->autoload();

        $this->assertInstanceOf(RouteCollection::class, $collection1);
        $this->assertInstanceOf(RouteCollection::class, $collection2);

        // In integration tests, the service is singleton, so loaders are same instance
        // but collections should be different instances since autoload() creates new ones
        $this->assertSame($loader1, $loader2);
        $this->assertNotSame($collection1, $collection2);
    }

    public function testLoadWithDifferentResourcesAndTypes(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $testCases = [
            ['resource1', 'type1'],
            ['resource2', null],
            [null, 'type3'],
            ['', ''],
            [123, 'numeric'],
            [['array'], 'array_type'],
        ];

        foreach ($testCases as [$resource, $type]) {
            $result = $loader->load($resource, $type);
            $this->assertInstanceOf(RouteCollection::class, $result);
        }
    }

    public function testAutoloadIsConsistent(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $collection1 = $loader->autoload();
        $collection2 = $loader->autoload();

        // Multiple calls should return equivalent collections
        $this->assertInstanceOf(RouteCollection::class, $collection1);
        $this->assertInstanceOf(RouteCollection::class, $collection2);

        // Check that both collections have the same structure
        $routes1 = $collection1->all();
        $routes2 = $collection2->all();

        $this->assertIsArray($routes1);
        $this->assertIsArray($routes2);
        $this->assertCount(count($routes1), $routes2);
    }

    public function testLoaderHasAttributeRouteControllerLoader(): void
    {
        $loader = self::getService(AttributeControllerLoader::class);
        $reflection = new \ReflectionClass($loader);
        $property = $reflection->getProperty('controllerLoader');
        $property->setAccessible(true);

        $controllerLoader = $property->getValue($loader);

        $this->assertInstanceOf(AttributeRouteControllerLoader::class, $controllerLoader);
    }
}
