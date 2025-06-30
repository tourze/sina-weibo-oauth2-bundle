<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Unit\Exception;

use PHPUnit\Framework\TestCase;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2ApiException;

class SinaWeiboOAuth2ApiExceptionTest extends TestCase
{
    public function testException(): void
    {
        $exception = new SinaWeiboOAuth2ApiException('Test message', 0, null, 'test_url', ['data']);
        
        $this->assertEquals('Test message', $exception->getMessage());
        $this->assertEquals('test_url', $exception->getApiUrl());
        $this->assertEquals(['data'], $exception->getResponseData());
    }
}