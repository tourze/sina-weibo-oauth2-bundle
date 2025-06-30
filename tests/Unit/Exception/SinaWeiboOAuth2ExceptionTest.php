<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2Exception;

class SinaWeiboOAuth2ExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new SinaWeiboOAuth2Exception('Test message', 0, null, ['context']);
        
        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals(['context'], $exception->getContext());
    }
}