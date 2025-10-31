<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2ConfigurationException;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2Exception;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2ConfigurationException::class)]
final class SinaWeiboOAuth2ConfigurationExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInheritsFromBaseException(): void
    {
        $exception = new SinaWeiboOAuth2ConfigurationException();

        $this->assertInstanceOf(SinaWeiboOAuth2Exception::class, $exception);
    }

    public function testExceptionWithDefaultValues(): void
    {
        $exception = new SinaWeiboOAuth2ConfigurationException();

        $this->assertSame('', $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertSame([], $exception->getContext());
    }

    public function testExceptionWithCustomMessage(): void
    {
        $message = 'Configuration is invalid';
        $exception = new SinaWeiboOAuth2ConfigurationException($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertSame([], $exception->getContext());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'Configuration is missing';
        $code = 1001;
        $exception = new SinaWeiboOAuth2ConfigurationException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertSame([], $exception->getContext());
    }

    public function testExceptionWithPreviousException(): void
    {
        $message = 'Configuration validation failed';
        $code = 1002;
        $previous = new \InvalidArgumentException('Missing app_id');
        $exception = new SinaWeiboOAuth2ConfigurationException($message, $code, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame([], $exception->getContext());
    }

    public function testExceptionWithContext(): void
    {
        $message = 'Configuration validation failed';
        $code = 1002;
        $previous = new \InvalidArgumentException('Missing app_id');
        $context = ['config_key' => 'app_id', 'required' => true];
        $exception = new SinaWeiboOAuth2ConfigurationException($message, $code, $previous, $context);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame($context, $exception->getContext());
    }

    public function testExceptionIsThrowable(): void
    {
        $exception = new SinaWeiboOAuth2ConfigurationException('No valid configuration found');

        $this->expectException(SinaWeiboOAuth2ConfigurationException::class);
        $this->expectExceptionMessage('No valid configuration found');

        throw $exception;
    }

    public function testExceptionContextIsImmutable(): void
    {
        $context = ['config_key' => 'app_secret'];
        $exception = new SinaWeiboOAuth2ConfigurationException('Test', 0, null, $context);

        $retrievedContext = $exception->getContext();
        $retrievedContext['new_field'] = 'new_value';

        $this->assertNotEquals($retrievedContext, $exception->getContext());
        $this->assertSame(['config_key' => 'app_secret'], $exception->getContext());
    }

    public function testExceptionWithComplexContext(): void
    {
        $context = [
            'missing_fields' => ['app_id', 'app_secret'],
            'invalid_fields' => ['scope'],
            'config_id' => 123,
            'validation_errors' => [
                'app_id' => 'Field is required',
                'scope' => 'Invalid scope format',
            ],
        ];

        $exception = new SinaWeiboOAuth2ConfigurationException(
            'Multiple configuration errors',
            1003,
            null,
            $context
        );

        $this->assertSame($context, $exception->getContext());
        $this->assertCount(4, $exception->getContext());
    }
}
