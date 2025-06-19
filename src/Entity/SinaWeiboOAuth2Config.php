<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2ConfigRepository;

#[ORM\Entity(repositoryClass: SinaWeiboOAuth2ConfigRepository::class)]
#[ORM\Table(name: 'sina_weibo_oauth2_config', options: ['comment' => '新浪微博OAuth2配置表'])]
class SinaWeiboOAuth2Config implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 255, unique: true, options: ['comment' => '微博应用ID'])]
    private string $appId;

    #[ORM\Column(type: Types::STRING, length: 500, options: ['comment' => '微博应用密钥'])]
    private string $appSecret;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => 'OAuth2授权范围'])]
    private ?string $scope = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用此配置', 'default' => true])]
    private bool $valid = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function setAppId(string $appId): self
    {
        $this->appId = $appId;
        return $this;
    }

    public function getAppSecret(): string
    {
        return $this->appSecret;
    }

    public function setAppSecret(string $appSecret): self
    {
        $this->appSecret = $appSecret;
        return $this;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): self
    {
        $this->scope = $scope;
        return $this;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): self
    {
        $this->valid = $valid;
        return $this;
    }

    /**
     * @deprecated Use isValid() instead
     */
    public function isActive(): bool
    {
        return $this->valid;
    }

    /**
     * @deprecated Use setValid() instead
     */
    public function setIsActive(bool $isActive): self
    {
        $this->valid = $isActive;
        return $this;
    }

    public function __toString(): string
    {
        return sprintf('SinaWeiboOAuth2Config[%s]', $this->appId ?? 'new');
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