<?php

namespace Tourze\SinaWeiboOAuth2Bundle;

use Doctrine\Bundle\DoctrineBundle\DoctrineBundle;
use Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Tourze\BundleDependency\BundleDependencyInterface;
use Tourze\RoutingAutoLoaderBundle\RoutingAutoLoaderBundle;

class SinaWeiboOAuth2Bundle extends AbstractBundle implements BundleDependencyInterface
{
    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // 在测试环境中手动添加路由配置
        if ('test' === $container->env()) {
            $builder->prependExtensionConfig('framework', [
                'router' => [
                    'resource' => '../config/routes.yaml',
                    'type' => 'yaml',
                ],
            ]);
        }
    }

    /**
     * @param array<mixed> $config
     */
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import('../config/services.yaml');

        $env = $container->env();
        if ('test' === $env) {
            $container->import('../config/services_test.yaml', ignoreErrors: true);
        }
    }

    /**
     * @return array<class-string<Bundle>, array<string, bool>>
     */
    public static function getBundleDependencies(): array
    {
        return [
            RoutingAutoLoaderBundle::class => ['all' => true],
            DoctrineBundle::class => ['all' => true],
            DoctrineFixturesBundle::class => ['all' => true],
        ];
    }
}
