<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Integration;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2ConfigRepository;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2StateRepository;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2UserRepository;
use Tourze\SinaWeiboOAuth2Bundle\Service\AttributeControllerLoader;
use Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service;
use Tourze\SinaWeiboOAuth2Bundle\Tests\TestKernel;

class SinaWeiboOAuth2BundleTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testServicesAreRegistered(): void
    {
        $kernel = self::bootKernel();
        $container = static::getContainer();

        // Test that main services are registered
        $this->assertTrue($container->has(SinaWeiboOAuth2Service::class));
        $this->assertTrue($container->has(SinaWeiboOAuth2ConfigRepository::class));
        $this->assertTrue($container->has(SinaWeiboOAuth2StateRepository::class));
        $this->assertTrue($container->has(SinaWeiboOAuth2UserRepository::class));
        $this->assertTrue($container->has(AttributeControllerLoader::class));

        // Test that services can be instantiated
        $oauth2Service = $container->get(SinaWeiboOAuth2Service::class);
        $this->assertInstanceOf(SinaWeiboOAuth2Service::class, $oauth2Service);

        $configRepo = $container->get(SinaWeiboOAuth2ConfigRepository::class);
        $this->assertInstanceOf(SinaWeiboOAuth2ConfigRepository::class, $configRepo);

        $stateRepo = $container->get(SinaWeiboOAuth2StateRepository::class);
        $this->assertInstanceOf(SinaWeiboOAuth2StateRepository::class, $stateRepo);

        $userRepo = $container->get(SinaWeiboOAuth2UserRepository::class);
        $this->assertInstanceOf(SinaWeiboOAuth2UserRepository::class, $userRepo);
    }

    public function testDoctrineMappingsAreLoaded(): void
    {
        $kernel = self::bootKernel();
        $em = static::getContainer()->get('doctrine')->getManager();

        // Test that entity metadata is properly loaded
        $configMetadata = $em->getClassMetadata('Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config');
        $this->assertEquals('sina_weibo_oauth2_config', $configMetadata->getTableName());

        $stateMetadata = $em->getClassMetadata('Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State');
        $this->assertEquals('sina_weibo_oauth2_state', $stateMetadata->getTableName());

        $userMetadata = $em->getClassMetadata('Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User');
        $this->assertEquals('sina_weibo_oauth2_user', $userMetadata->getTableName());
    }

    public function testCommandsAreRegistered(): void
    {
        $kernel = self::bootKernel();
        
        // Get the console application
        $application = new \Symfony\Bundle\FrameworkBundle\Console\Application($kernel);
        $application->setAutoExit(false);

        // Test that commands are registered
        $this->assertTrue($application->has('sina-weibo-oauth2:config'));
        $this->assertTrue($application->has('sina-weibo-oauth2:cleanup'));
        $this->assertTrue($application->has('sina-weibo-oauth2:refresh-tokens'));
    }

    public function testRoutesAreLoaded(): void
    {
        $kernel = self::bootKernel();
        $router = static::getContainer()->get('router');

        // Test that routes are registered
        $routes = $router->getRouteCollection();
        
        $this->assertNotNull($routes->get('sina_weibo_oauth2_login'));
        $this->assertNotNull($routes->get('sina_weibo_oauth2_callback'));

        // Test route paths
        $loginRoute = $routes->get('sina_weibo_oauth2_login');
        $this->assertEquals('/sina-weibo-oauth2/login', $loginRoute->getPath());

        $callbackRoute = $routes->get('sina_weibo_oauth2_callback');
        $this->assertEquals('/sina-weibo-oauth2/callback', $callbackRoute->getPath());
    }

    public function testBundleConfiguration(): void
    {
        $kernel = self::bootKernel();
        $container = static::getContainer();

        // Test that bundle is properly configured
        $bundles = $kernel->getBundles();
        $this->assertArrayHasKey('SinaWeiboOAuth2Bundle', $bundles);

        // Test that required dependencies are available
        $this->assertTrue($container->has('doctrine'));
        $this->assertTrue($container->has('http_client'));
        $this->assertTrue($container->has('router'));
    }
}