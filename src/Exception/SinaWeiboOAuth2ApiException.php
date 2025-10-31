<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Exception;

class SinaWeiboOAuth2ApiException extends SinaWeiboOAuth2Exception
{
    /**
     * @param array<string, mixed> $context
     * @param array<string, mixed>|null $responseData
     */
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        private readonly ?string $apiUrl = null,
        private readonly ?array $responseData = null,
        array $context = [],
    ) {
        parent::__construct($message, $code, $previous, $context);
    }

    public function getApiUrl(): ?string
    {
        return $this->apiUrl;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getResponseData(): ?array
    {
        return $this->responseData;
    }
}
