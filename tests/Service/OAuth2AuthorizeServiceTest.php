<?php

namespace Tests\Tourze\SinaWeiboOAuth2Bundle\Service;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboAppConfig;
use Tourze\SinaWeiboOAuth2Bundle\Exception\AuthorizationException;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboAppConfigRepository;
use Tourze\SinaWeiboOAuth2Bundle\Service\OAuth2AuthorizeService;

class OAuth2AuthorizeServiceTest extends TestCase
{
    private OAuth2AuthorizeService $service;
    private SinaWeiboAppConfigRepository|MockObject $appConfigRepository;

    protected function setUp(): void
    {
        $this->appConfigRepository = $this->createMock(SinaWeiboAppConfigRepository::class);
        $this->service = new OAuth2AuthorizeService($this->appConfigRepository);
    }

    public function testGenerateAuthorizeUrlSuccess(): void
    {
        $appKey = 'test_app_key';
        $appConfig = new SinaWeiboAppConfig();
        $appConfig->setAppKey($appKey)
            ->setRedirectUri('http://example.com/callback')
            ->setScope('email')
            ->setValid(true);

        $this->appConfigRepository->expects($this->once())
            ->method('findByAppKey')
            ->with($appKey)
            ->willReturn($appConfig);

        $url = $this->service->generateAuthorizeUrl($appKey);

        $this->assertStringContainsString('https://api.weibo.com/oauth2/authorize', $url);
        $this->assertStringContainsString('client_id=' . $appKey, $url);
        $this->assertStringContainsString('response_type=code', $url);
    }

    public function testGenerateAuthorizeUrlAppNotFound(): void
    {
        $this->appConfigRepository->expects($this->once())
            ->method('findByAppKey')
            ->with('invalid_key')
            ->willReturn(null);

        $this->expectException(AuthorizationException::class);
        $this->service->generateAuthorizeUrl('invalid_key');
    }

    public function testHandleCallbackSuccess(): void
    {
        $callbackParams = [
            'code' => 'test_code',
            'state' => 'test_state'
        ];

        $code = $this->service->handleCallback($callbackParams, 'test_state');
        $this->assertEquals('test_code', $code);
    }

    public function testHandleCallbackError(): void
    {
        $callbackParams = [
            'error' => 'access_denied',
            'error_description' => 'User denied authorization'
        ];

        $this->expectException(AuthorizationException::class);
        $this->service->handleCallback($callbackParams);
    }

    public function testGenerateState(): void
    {
        $state1 = $this->service->generateState();
        $state2 = $this->service->generateState();

        $this->assertIsString($state1);
        $this->assertIsString($state2);
        $this->assertNotEquals($state1, $state2);
        $this->assertEquals(32, strlen($state1));
    }

    public function testValidateCallbackParamsSuccess(): void
    {
        $params = ['code' => 'test_code', 'state' => 'test_state'];
        $code = $this->service->validateCallbackParams($params, 'test_state');
        $this->assertEquals('test_code', $code);
    }

    public function testValidateCallbackParamsMissingCode(): void
    {
        $this->expectException(AuthorizationException::class);
        $this->expectExceptionMessage('缺少授权码参数');
        $this->service->validateCallbackParams([]);
    }
}
