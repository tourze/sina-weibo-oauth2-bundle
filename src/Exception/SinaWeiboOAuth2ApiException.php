<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Exception;

class SinaWeiboOAuth2ApiException extends SinaWeiboOAuth2Exception
{
    private ?string $apiUrl;
    private ?array $responseData;

    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        ?string $apiUrl = null,
        ?array $responseData = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->apiUrl = $apiUrl;
        $this->responseData = $responseData;
    }

    public function getApiUrl(): ?string
    {
        return $this->apiUrl;
    }

    public function getResponseData(): ?array
    {
        return $this->responseData;
    }
}