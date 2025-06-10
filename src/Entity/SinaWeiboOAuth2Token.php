<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2TokenRepository;

/**
 * 新浪微博OAuth2令牌实体
 * 用于存储用户的OAuth2授权令牌信息
 */
#[ORM\Entity(repositoryClass: SinaWeiboOAuth2TokenRepository::class)]
#[ORM\Table(name: 'sina_weibo_oauth2_token', options: ['comment' => '新浪微博OAuth2令牌表'])]
class SinaWeiboOAuth2Token implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: SinaWeiboAppConfig::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: 'app_config_id', referencedColumnName: 'id', nullable: false)]
    private ?SinaWeiboAppConfig $appConfig = null;

    #[ORM\ManyToOne(targetEntity: UserInterface::class, fetch: 'EXTRA_LAZY')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: true)]
    private ?UserInterface $user = null;

    #[IndexColumn]
    #[ORM\Column(type: 'string', length: 50, nullable: false, options: ['comment' => '微博用户UID'])]
    private ?string $weiboUid = null;

    #[ORM\Column(type: 'text', nullable: false, options: ['comment' => '访问令牌(加密存储)'])]
    private ?string $accessToken = null;

    #[ORM\Column(type: 'string', length: 50, nullable: false, options: ['comment' => '令牌类型，通常为Bearer'])]
    private string $tokenType = 'Bearer';

    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => '令牌有效期(秒)'])]
    private ?int $expiresIn = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['comment' => '令牌过期时间'])]
    private ?\DateTime $expiresTime = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => '刷新令牌(加密存储)'])]
    private ?string $refreshToken = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['comment' => '刷新令牌过期时间'])]
    private ?\DateTime $refreshExpiresTime = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true, options: ['comment' => '权限范围'])]
    private ?string $scope = null;

    #[IndexColumn]
    #[ORM\Column(type: 'boolean', nullable: false, options: ['comment' => '是否有效'])]
    private bool $valid = true;

    #[CreateTimeColumn]
    #[ORM\Column(type: 'datetime', nullable: false, options: ['comment' => '创建时间'])]
    private ?\DateTime $createTime = null;

    #[UpdateTimeColumn]
    #[ORM\Column(type: 'datetime', nullable: false, options: ['comment' => '更新时间'])]
    private ?\DateTime $updateTime = null;

    public function __toString(): string
    {
        $appKey = $this->appConfig?->getAppKey() ?? 'unknown';
        return "WeiboToken#{$this->weiboUid}@{$appKey}";
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppConfig(): ?SinaWeiboAppConfig
    {
        return $this->appConfig;
    }

    public function setAppConfig(?SinaWeiboAppConfig $appConfig): static
    {
        $this->appConfig = $appConfig;
        return $this;
    }

    public function getUser(): ?UserInterface
    {
        return $this->user;
    }

    public function setUser(?UserInterface $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getWeiboUid(): ?string
    {
        return $this->weiboUid;
    }

    public function setWeiboUid(string $weiboUid): static
    {
        $this->weiboUid = $weiboUid;
        return $this;
    }

    public function getAccessToken(): ?string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): static
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getTokenType(): string
    {
        return $this->tokenType;
    }

    public function setTokenType(string $tokenType): static
    {
        $this->tokenType = $tokenType;
        return $this;
    }

    public function getExpiresIn(): ?int
    {
        return $this->expiresIn;
    }

    public function setExpiresIn(?int $expiresIn): static
    {
        $this->expiresIn = $expiresIn;

        if ($expiresIn !== null) {
            $this->expiresTime = new \DateTime('+' . $expiresIn . ' seconds');
        }

        return $this;
    }

    public function getExpiresTime(): ?\DateTime
    {
        return $this->expiresTime;
    }

    public function setExpiresTime(?\DateTime $expiresTime): static
    {
        $this->expiresTime = $expiresTime;
        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): static
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    public function getRefreshExpiresTime(): ?\DateTime
    {
        return $this->refreshExpiresTime;
    }

    public function setRefreshExpiresTime(?\DateTime $refreshExpiresTime): static
    {
        $this->refreshExpiresTime = $refreshExpiresTime;
        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): static
    {
        $this->scope = $scope;
        return $this;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): static
    {
        $this->valid = $valid;
        return $this;
    }

    public function getCreateTime(): ?\DateTime
    {
        return $this->createTime;
    }

    public function setCreateTime(\DateTime $createTime): static
    {
        $this->createTime = $createTime;
        return $this;
    }

    public function getUpdateTime(): ?\DateTime
    {
        return $this->updateTime;
    }

    public function setUpdateTime(\DateTime $updateTime): static
    {
        $this->updateTime = $updateTime;
        return $this;
    }

    /**
     * 检查访问令牌是否有效
     */
    public function isTokenValid(): bool
    {
        if (!$this->valid) {
            return false;
        }

        if ($this->expiresTime === null) {
            return true;
        }

        return $this->expiresTime > new \DateTime();
    }

    /**
     * 检查刷新令牌是否有效
     */
    public function isRefreshTokenValid(): bool
    {
        if ($this->refreshToken === null) {
            return false;
        }

        if ($this->refreshExpiresTime === null) {
            return true;
        }

        return $this->refreshExpiresTime > new \DateTime();
    }
}
