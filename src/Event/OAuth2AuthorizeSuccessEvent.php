<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Event;

use Symfony\Contracts\EventDispatcher\Event;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Token;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboUserInfo;

/**
 * OAuth2授权成功事件
 * 当用户成功完成OAuth2授权并获取到访问令牌时触发
 */
class OAuth2AuthorizeSuccessEvent extends Event
{
    public const NAME = 'sina_weibo_oauth2.authorize.success';

    public function __construct(
        private readonly SinaWeiboOAuth2Token $token,
        private readonly ?SinaWeiboUserInfo $userInfo = null,
        private readonly array $context = []
    ) {}

    /**
     * 获取OAuth2令牌信息
     */
    public function getToken(): SinaWeiboOAuth2Token
    {
        return $this->token;
    }

    /**
     * 获取用户信息（如果已获取）
     */
    public function getUserInfo(): ?SinaWeiboUserInfo
    {
        return $this->userInfo;
    }

    /**
     * 获取上下文信息
     */
    public function getContext(): array
    {
        return $this->context;
    }

    /**
     * 获取微博用户UID
     */
    public function getWeiboUid(): string
    {
        return $this->token->getWeiboUid();
    }

    /**
     * 获取应用配置
     */
    public function getAppConfig(): ?\Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboAppConfig
    {
        return $this->token->getAppConfig();
    }

    /**
     * 获取关联的用户（如果有）
     */
    public function getUser(): ?\Symfony\Component\Security\Core\User\UserInterface
    {
        return $this->token->getUser();
    }
}
