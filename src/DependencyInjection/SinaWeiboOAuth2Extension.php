<?php

namespace Tourze\SinaWeiboOAuth2Bundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

class SinaWeiboOAuth2Extension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }
}
