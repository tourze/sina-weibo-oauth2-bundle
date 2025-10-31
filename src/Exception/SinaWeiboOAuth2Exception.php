<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Exception;

abstract class SinaWeiboOAuth2Exception extends \RuntimeException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        /** @var array<string, mixed> */
        private readonly array $context = [],
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array<string, mixed>
     */
    public function getContext(): array
    {
        return $this->context;
    }
}
