<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboAppConfig;

/**
 * OAuth2授权失败事件
 * 当OAuth2授权过程中发生错误时触发
 */
class OAuth2AuthorizeFailureEvent extends Event
{
    public const NAME = 'sina_weibo_oauth2.authorize.failure';

    public function __construct(
        private readonly \Throwable $exception,
        private readonly ?SinaWeiboAppConfig $appConfig = null,
        private readonly ?string $code = null,
        private readonly array $context = []
    ) {}

    /**
     * 获取异常信息
     */
    public function getException(): \Throwable
    {
        return $this->exception;
    }

    /**
     * 获取错误消息
     */
    public function getErrorMessage(): string
    {
        return $this->exception->getMessage();
    }

    /**
     * 获取应用配置
     */
    public function getAppConfig(): ?SinaWeiboAppConfig
    {
        return $this->appConfig;
    }

    /**
     * 获取授权码（如果有）
     */
    public function getCode(): ?string
    {
        return $this->code;
    }

    /**
     * 获取上下文信息
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * 获取应用Key
     */
    public function getAppKey(): ?string
    {
        return $this->appConfig?->getAppKey();
    }

    /**
     * 判断是否为网络错误
     */
    public function isNetworkError(): bool
    {
        return $this->exception instanceof \Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
    }

    /**
     * 判断是否为API错误
     */
    public function isApiError(): bool
    {
        return $this->exception instanceof \Tourze\SinaWeiboOAuth2Bundle\Exception\ApiException;
    }

    /**
     * 判断是否为授权错误
     */
    public function isAuthorizationError(): bool
    {
        return $this->exception instanceof \Tourze\SinaWeiboOAuth2Bundle\Exception\AuthorizationException;
    }
}
