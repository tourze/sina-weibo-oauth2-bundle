<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Controller;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Tourze\PHPUnitSymfonyWebTest\AbstractWebTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Controller\SinaWeiboOAuth2LoginController;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2LoginController::class)]
#[RunTestsInSeparateProcesses]
final class SinaWeiboOAuth2LoginControllerTest extends AbstractWebTestCase
{
    public function testControllerIsInvokable(): void
    {
        $client = self::createClientWithDatabase();
        $this->assertInstanceOf(SinaWeiboOAuth2LoginController::class, self::getService(SinaWeiboOAuth2LoginController::class));
    }

    public function testLoginReturnsRedirectResponse(): void
    {
        $client = self::createClientWithDatabase();

        $client->request('GET', '/sina-weibo-oauth2/login');

        $this->assertEquals(302, $client->getResponse()->getStatusCode());
    }

    #[DataProvider('provideNotAllowedMethods')]
    public function testMethodNotAllowed(string $method): void
    {
        $client = self::createClientWithDatabase();

        $this->expectException(MethodNotAllowedHttpException::class);

        $client->request($method, '/sina-weibo-oauth2/login');
    }
}
