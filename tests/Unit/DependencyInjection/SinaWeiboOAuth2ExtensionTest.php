<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Unit\DependencyInjection;

use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\SinaWeiboOAuth2Bundle\DependencyInjection\SinaWeiboOAuth2Extension;

class SinaWeiboOAuth2ExtensionTest extends TestCase
{
    public function testLoad(): void
    {
        $container = new ContainerBuilder();
        $extension = new SinaWeiboOAuth2Extension();
        
        $extension->load([], $container);
        
        $this->assertTrue($container->hasDefinition('Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service'));
    }
}