<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2ConfigurationException;

class SinaWeiboOAuth2ConfigurationExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new SinaWeiboOAuth2ConfigurationException('Test config message');
        
        $this->assertEquals('Test config message', $exception->getMessage());
        $this->assertInstanceOf(\Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2Exception::class, $exception);
    }
}