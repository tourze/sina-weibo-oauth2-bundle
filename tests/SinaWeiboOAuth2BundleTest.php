<?php

declare(strict_types=1);

namespace Tourze\SinaWeiboOAuth2Bundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;
use Tourze\SinaWeiboOAuth2Bundle\SinaWeiboOAuth2Bundle;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2Bundle::class)]
#[RunTestsInSeparateProcesses]
final class SinaWeiboOAuth2BundleTest extends AbstractBundleTestCase
{
}
