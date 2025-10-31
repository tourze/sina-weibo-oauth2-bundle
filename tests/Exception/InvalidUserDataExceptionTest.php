<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Exception\InvalidUserDataException;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2Exception;

/**
 * @internal
 */
#[CoversClass(InvalidUserDataException::class)]
final class InvalidUserDataExceptionTest extends AbstractExceptionTestCase
{
    public function testExceptionInheritsFromBaseException(): void
    {
        $exception = new InvalidUserDataException();

        $this->assertInstanceOf(SinaWeiboOAuth2Exception::class, $exception);
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
        $message = 'User data is invalid';
        $exception = new InvalidUserDataException($message);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame(0, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertSame([], $exception->getContext());
    }

    public function testExceptionWithMessageAndCode(): void
    {
        $message = 'User data is invalid';
        $code = 400;
        $exception = new InvalidUserDataException($message, $code);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertNull($exception->getPrevious());
        $this->assertSame([], $exception->getContext());
    }

    public function testExceptionWithPreviousException(): void
    {
        $message = 'User data is invalid';
        $code = 400;
        $previous = new \RuntimeException('Previous exception');
        $exception = new InvalidUserDataException($message, $code, $previous);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame([], $exception->getContext());
    }

    public function testExceptionWithContext(): void
    {
        $message = 'User data is invalid';
        $code = 400;
        $previous = new \RuntimeException('Previous exception');
        $context = ['field' => 'uid', 'value' => null];
        $exception = new InvalidUserDataException($message, $code, $previous, $context);

        $this->assertSame($message, $exception->getMessage());
        $this->assertSame($code, $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
        $this->assertSame($context, $exception->getContext());
    }

    public function testExceptionIsThrowable(): void
    {
        $exception = new InvalidUserDataException('Test message');

        $this->expectException(InvalidUserDataException::class);
        $this->expectExceptionMessage('Test message');

        throw $exception;
    }

    public function testExceptionContextIsImmutable(): void
    {
        $context = ['field' => 'uid'];
        $exception = new InvalidUserDataException('Test', 0, null, $context);

        $retrievedContext = $exception->getContext();
        $retrievedContext['new_field'] = 'new_value';

        $this->assertNotEquals($retrievedContext, $exception->getContext());
        $this->assertSame(['field' => 'uid'], $exception->getContext());
    }
}
