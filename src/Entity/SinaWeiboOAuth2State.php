<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
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
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, unique: true, options: ['comment' => 'OAuth2状态码'])]
    #[Assert\NotBlank(message: 'State cannot be empty')]
    #[Assert\Length(max: 255, maxMessage: 'State cannot be longer than {{ limit }} characters')]
    private string $state;

    #[ORM\ManyToOne(targetEntity: SinaWeiboOAuth2Config::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    private SinaWeiboOAuth2Config $config;

    #[IndexColumn]
    #[ORM\Column(name: 'expire_time', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '过期时间'])]
    #[Assert\NotNull(message: 'Expire time cannot be null')]
    private \DateTimeImmutable $expireTime;

    #[IndexColumn]
    #[ORM\Column(name: 'session_id', type: Types::STRING, length: 255, nullable: true, options: ['comment' => '会话ID'])]
    #[Assert\Length(max: 255, maxMessage: 'Session ID cannot be longer than {{ limit }} characters')]
    private ?string $sessionId = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否已使用', 'default' => false])]
    #[Assert\NotNull(message: 'Used status cannot be null')]
    private bool $used = false;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function setState(string $state): void
    {
        $this->state = $state;
    }

    public function getConfig(): SinaWeiboOAuth2Config
    {
        return $this->config;
    }

    public function setConfig(SinaWeiboOAuth2Config $config): void
    {
        $this->config = $config;
    }

    public function getExpireTime(): \DateTimeImmutable
    {
        return $this->expireTime;
    }

    public function setExpireTime(\DateTimeImmutable $expireTime): void
    {
        $this->expireTime = $expireTime;
    }

    public function setExpiresInMinutes(int $expiresInMinutes): void
    {
        if ($expiresInMinutes >= 0) {
            $this->expireTime = new \DateTimeImmutable(sprintf('+%d minutes', $expiresInMinutes));
        } else {
            $this->expireTime = new \DateTimeImmutable(sprintf('%d minutes', $expiresInMinutes));
        }
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(?string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function isUsed(): bool
    {
        return $this->used;
    }

    public function markAsUsed(): void
    {
        $this->used = true;
    }

    public function isValid(): bool
    {
        if (!isset($this->expireTime)) {
            return false; // 未设置过期时间认为无效
        }

        return !$this->used && $this->expireTime > new \DateTimeImmutable();
    }

    public function isExpired(): bool
    {
        if (!isset($this->expireTime)) {
            return true; // 未设置过期时间认为已过期
        }

        return $this->expireTime <= new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('SinaWeiboOAuth2State[%s]', $this->state);
    }

    // Compatibility methods for tests
    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->getCreateTime();
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->getUpdateTime();
    }
}
