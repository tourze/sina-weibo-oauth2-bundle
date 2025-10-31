<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2ApiException;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2Exception;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2ApiException::class)]
final class SinaWeiboOAuth2ApiExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInheritsFromBaseException(): void
    {
        $exception = new SinaWeiboOAuth2ApiException();

        $this->assertInstanceOf(SinaWeiboOAuth2Exception::class, $exception);
    }

    public function testExceptionWithDefaultValues(): void
    {
        $exception = new SinaWeiboOAuth2ApiException();

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertNull($exception->getApiUrl());
        $this->assertNull($exception->getResponseData());
    }

    public function testExceptionWithCustomMessage(): void
    {
        $message = 'API request failed';
        $exception = new SinaWeiboOAuth2ApiException($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertNull($exception->getApiUrl());
        $this->assertNull($exception->getResponseData());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'API request failed';
        $code = 500;
        $exception = new SinaWeiboOAuth2ApiException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertNull($exception->getApiUrl());
        $this->assertNull($exception->getResponseData());
    }

    public function testExceptionWithPreviousException(): void
    {
        $message = 'API request failed';
        $code = 500;
        $previous = new \RuntimeException('HTTP error');
        $exception = new SinaWeiboOAuth2ApiException($message, $code, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertNull($exception->getApiUrl());
        $this->assertNull($exception->getResponseData());
    }

    public function testExceptionWithApiUrl(): void
    {
        $message = 'API request failed';
        $code = 500;
        $previous = new \RuntimeException('HTTP error');
        $apiUrl = 'https://api.weibo.com/oauth2/access_token';
        $exception = new SinaWeiboOAuth2ApiException($message, $code, $previous, $apiUrl);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame($apiUrl, $exception->getApiUrl());
        $this->assertNull($exception->getResponseData());
    }

    public function testExceptionWithResponseData(): void
    {
        $message = 'API request failed';
        $code = 500;
        $previous = new \RuntimeException('HTTP error');
        $apiUrl = 'https://api.weibo.com/oauth2/access_token';
        $responseData = ['error' => 'invalid_grant', 'error_description' => 'The provided authorization grant is invalid'];
        $exception = new SinaWeiboOAuth2ApiException($message, $code, $previous, $apiUrl, $responseData);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame($apiUrl, $exception->getApiUrl());
        $this->assertSame($responseData, $exception->getResponseData());
    }

    public function testExceptionWithAllParameters(): void
    {
        $message = 'Token exchange failed';
        $code = 400;
        $previous = new \Exception('Network error');
        $apiUrl = 'https://api.weibo.com/oauth2/access_token';
        $responseData = [
            'error' => 'invalid_client',
            'error_description' => 'Invalid client credentials',
            'timestamp' => time(),
        ];

        $exception = new SinaWeiboOAuth2ApiException($message, $code, $previous, $apiUrl, $responseData);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame($apiUrl, $exception->getApiUrl());
        $this->assertSame($responseData, $exception->getResponseData());
    }

    public function testExceptionIsThrowable(): void
    {
        $exception = new SinaWeiboOAuth2ApiException('API error');

        $this->expectException(SinaWeiboOAuth2ApiException::class);
        $this->expectExceptionMessage('API error');

        throw $exception;
    }

    public function testResponseDataIsImmutable(): void
    {
        $responseData = ['error' => 'invalid_grant'];
        $exception = new SinaWeiboOAuth2ApiException('Test', 0, null, null, $responseData);

        $retrievedData = $exception->getResponseData();
        $retrievedData['new_field'] = 'new_value';

        $this->assertNotEquals($retrievedData, $exception->getResponseData());
        $this->assertSame(['error' => 'invalid_grant'], $exception->getResponseData());
    }

    public function testEmptyStringApiUrl(): void
    {
        $exception = new SinaWeiboOAuth2ApiException('Test', 0, null, '');

        $this->assertSame('', $exception->getApiUrl());
    }

    public function testEmptyArrayResponseData(): void
    {
        $exception = new SinaWeiboOAuth2ApiException('Test', 0, null, null, []);

        $this->assertSame([], $exception->getResponseData());
    }
}
