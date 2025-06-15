<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2StateRepository;

#[ORM\Entity(repositoryClass: SinaWeiboOAuth2StateRepository::class)]
#[ORM\Table(name: 'sina_weibo_oauth2_state', options: ['comment' => '新浪微博OAuth2状态表'])]
class SinaWeiboOAuth2State implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, unique: true, options: ['comment' => 'OAuth2状态码'])]
    private string $state;

    #[ORM\ManyToOne(targetEntity: SinaWeiboOAuth2Config::class)]
    #[ORM\JoinColumn(nullable: false)]
    private SinaWeiboOAuth2Config $config;

    #[IndexColumn]
    #[ORM\Column(type: Types::DATETIME_MUTABLE, options: ['comment' => '过期时间'])]
    private \DateTime $expireTime;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '会话ID'])]
    private ?string $sessionId = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否已使用', 'default' => false])]
    private bool $used = false;

    public function __construct(string $state, SinaWeiboOAuth2Config $config, int $expiresInMinutes = 10)
    {
        $this->state = $state;
        $this->config = $config;
        $this->expireTime = new \DateTime(sprintf('+%d minutes', $expiresInMinutes));
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function getConfig(): SinaWeiboOAuth2Config
    {
        return $this->config;
    }

    public function getExpireTime(): \DateTime
    {
        return $this->expireTime;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): self
    {
        $this->sessionId = $sessionId;
        return $this;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function markAsUsed(): self
    {
        $this->used = true;
        return $this;
    }

    public function isValid(): bool
    {
        return !$this->used && $this->expireTime > new \DateTime();
    }

    public function isExpired(): bool
    {
        return $this->expireTime <= new \DateTime();
    }

    public function __toString(): string
    {
        return sprintf('SinaWeiboOAuth2State[%s]', $this->state ?? 'new');
    }
}