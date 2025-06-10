<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Exception;

/**
 * 新浪微博OAuth2异常基类
 */
class SinaWeiboOAuth2Exception extends \Exception
{
    public function __construct(string $message = '', int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
