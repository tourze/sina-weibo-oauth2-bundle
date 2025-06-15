<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\HttpKernel\Kernel;
use Tourze\DoctrineIndexedBundle\DoctrineIndexedBundle;
use Tourze\DoctrineTimestampBundle\DoctrineTimestampBundle;
use Tourze\SinaWeiboOAuth2Bundle\SinaWeiboOAuth2Bundle;

class TestKernel extends Kernel
{
    public function registerBundles(): iterable
    {
        return [
            new FrameworkBundle(),
            new DoctrineBundle(),
            new SecurityBundle(),
            new DoctrineIndexedBundle(),
            new DoctrineTimestampBundle(),
            new SinaWeiboOAuth2Bundle(),
        ];
    }

    public function registerContainerConfiguration(LoaderInterface $loader): void
    {
        $loader->load(__DIR__ . '/config/test.yaml');
    }

    public function getProjectDir(): string
    {
        return __DIR__ . '/..';
    }
}