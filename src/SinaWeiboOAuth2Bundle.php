<?php

namespace Tourze\SinaWeiboOAuth2Bundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;

class SinaWeiboOAuth2Bundle extends AbstractBundle
{
    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $container->import(__DIR__ . '/Resources/config/services.yaml');
    }

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        // Route loading
        $routesPath = __DIR__ . '/Resources/config/routes.yaml';
        if (file_exists($routesPath)) {
            $builder->prependExtensionConfig('framework', [
                'router' => [
                    'resource' => $routesPath,
                    'type' => 'yaml',
                ],
            ]);
        }
    }
}
