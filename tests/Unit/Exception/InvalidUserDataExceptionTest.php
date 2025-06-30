<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\SinaWeiboOAuth2Bundle\Exception\InvalidUserDataException;

class InvalidUserDataExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new InvalidUserDataException('Invalid user data');
        
        $this->assertEquals('Invalid user data', $exception->getMessage());
        $this->assertInstanceOf(\Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2Exception::class, $exception);
    }
}