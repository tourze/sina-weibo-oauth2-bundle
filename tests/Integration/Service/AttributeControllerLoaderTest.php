<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Integration\Service;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Service\AttributeControllerLoader;
use Tourze\SinaWeiboOAuth2Bundle\Tests\TestKernel;

class AttributeControllerLoaderTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testServiceExists(): void
    {
        self::bootKernel();
        $service = self::getContainer()->get(AttributeControllerLoader::class);
        
        $this->assertInstanceOf(AttributeControllerLoader::class, $service);
    }
}