<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Exception\InvalidUserDataException;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2ApiException;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2Exception;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2Exception::class)]
final class SinaWeiboOAuth2ExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInheritsFromBaseException(): void
    {
        $exception = new InvalidUserDataException();

        $this->assertInstanceOf(\Exception::class, $exception);
    }

    public function testExceptionWithDefaultValues(): void
    {
        $exception = new InvalidUserDataException();

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertSame([], $exception->getContext());
    }

    public function testExceptionWithCustomMessage(): void
    {
        $message = 'OAuth2 error occurred';
        $exception = new InvalidUserDataException($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertSame([], $exception->getContext());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'Authentication failed';
        $code = 401;
        $exception = new InvalidUserDataException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertSame([], $exception->getContext());
    }

    public function testExceptionWithPreviousException(): void
    {
        $message = 'Token validation failed';
        $code = 403;
        $previous = new \RuntimeException('Network timeout');
        $exception = new InvalidUserDataException($message, $code, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame([], $exception->getContext());
    }

    public function testExceptionWithContext(): void
    {
        $message = 'User not found';
        $code = 404;
        $previous = new \RuntimeException('Database error');
        $context = ['uid' => '12345', 'config_id' => 1];
        $exception = new InvalidUserDataException($message, $code, $previous, $context);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame($context, $exception->getContext());
    }

    public function testExceptionWithAllParameters(): void
    {
        $message = 'State validation failed';
        $code = 400;
        $previous = new \InvalidArgumentException('Invalid state parameter');
        $context = [
            'state' => 'invalid_state_123',
            'expected_state' => 'valid_state_456',
            'timestamp' => time(),
            'ip_address' => '192.168.1.1',
        ];

        $exception = new InvalidUserDataException($message, $code, $previous, $context);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame($context, $exception->getContext());
    }

    public function testExceptionIsThrowable(): void
    {
        $exception = new InvalidUserDataException('General OAuth2 error');

        $this->expectException(InvalidUserDataException::class);
        $this->expectExceptionMessage('General OAuth2 error');

        throw $exception;
    }

    public function testExceptionContextIsImmutable(): void
    {
        $context = ['uid' => '12345'];
        $exception = new InvalidUserDataException('Test', 0, null, $context);

        $retrievedContext = $exception->getContext();
        $retrievedContext['new_field'] = 'new_value';

        $this->assertNotEquals($retrievedContext, $exception->getContext());
        $this->assertSame(['uid' => '12345'], $exception->getContext());
    }

    public function testExceptionWithEmptyContext(): void
    {
        $exception = new SinaWeiboOAuth2ApiException('Test', 0, null, null, null, []);

        $this->assertSame([], $exception->getContext());
        $this->assertCount(0, $exception->getContext());
    }

    public function testExceptionWithComplexContext(): void
    {
        $context = [
            'user_data' => [
                'uid' => '123456',
                'screen_name' => 'test_user',
            ],
            'request_info' => [
                'url' => 'https://api.weibo.com/2/users/show.json',
                'method' => 'GET',
                'headers' => ['Authorization' => 'Bearer [HIDDEN]'],
            ],
            'error_details' => [
                'error_code' => 21327,
                'error_message' => 'Token expired',
            ],
            'debug_info' => [
                'timestamp' => '2023-01-01 12:00:00',
                'trace_id' => 'abc123def456',
            ],
        ];

        $exception = new SinaWeiboOAuth2ApiException('Complex error', 500, null, null, null, $context);

        $this->assertSame($context, $exception->getContext());
        $this->assertCount(4, $exception->getContext());
        $this->assertArrayHasKey('user_data', $exception->getContext());
        $this->assertArrayHasKey('request_info', $exception->getContext());
        $this->assertArrayHasKey('error_details', $exception->getContext());
        $this->assertArrayHasKey('debug_info', $exception->getContext());
    }

    public function testExceptionContextWithNullValues(): void
    {
        $context = [
            'uid' => null,
            'access_token' => null,
            'refresh_token' => '',
            'expires_at' => 0,
        ];

        $exception = new SinaWeiboOAuth2ApiException('Null context test', 0, null, null, null, $context);

        $this->assertSame($context, $exception->getContext());
        $this->assertArrayHasKey('uid', $exception->getContext());
        $this->assertArrayHasKey('access_token', $exception->getContext());
        $this->assertSame('', $exception->getContext()['refresh_token']);
        $this->assertSame(0, $exception->getContext()['expires_at']);
    }
}
