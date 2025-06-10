<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Exception;

/**
 * 授权异常
 * 当OAuth2授权过程中出现问题时抛出
 */
class AuthorizationException extends SinaWeiboOAuth2Exception
{
    /**
     * 错误代码常量
     */
    public const INVALID_CLIENT = 'invalid_client';
    public const INVALID_REQUEST = 'invalid_request';
    public const INVALID_GRANT = 'invalid_grant';
    public const UNAUTHORIZED_CLIENT = 'unauthorized_client';
    public const UNSUPPORTED_GRANT_TYPE = 'unsupported_grant_type';
    public const ACCESS_DENIED = 'access_denied';
    public const INVALID_SCOPE = 'invalid_scope';

    private string $errorCode;
    private ?string $errorDescription;

    public function __construct(
        string $errorCode,
        ?string $errorDescription = null,
        int $httpStatusCode = 400,
        ?\Throwable $previous = null
    ) {
        $this->errorCode = $errorCode;
        $this->errorDescription = $errorDescription;

        $message = "OAuth2 Authorization Error: {$errorCode}";
        if ($errorDescription) {
            $message .= " - {$errorDescription}";
        }

        parent::__construct($message, $httpStatusCode, $previous);
    }

    public function getErrorCode(): string
    {
        return $this->errorCode;
    }

    public function getErrorDescription(): ?string
    {
        return $this->errorDescription;
    }

    public function toArray(): array
    {
        return [
            'error' => $this->errorCode,
            'error_description' => $this->errorDescription,
            'error_code' => $this->getCode()
        ];
    }
}
