<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Exception;

/**
 * API异常
 * 当调用新浪微博API时出现错误时抛出
 */
class ApiException extends SinaWeiboOAuth2Exception
{
    private ?array $responseData;

    public function __construct(
        string $message,
        int $code = 0,
        ?array $responseData = null,
        ?\Throwable $previous = null
    ) {
        $this->responseData = $responseData;
        parent::__construct($message, $code, $previous);
    }

    public function getResponseData(): ?array
    {
        return $this->responseData;
    }

    /**
     * 从新浪微博API错误响应创建异常
     */
    public static function fromApiResponse(array $response): self
    {
        $message = $response['error_description'] ?? $response['error'] ?? 'API Error';
        $code = $response['error_code'] ?? 0;

        return new self($message, $code, $response);
    }
}
