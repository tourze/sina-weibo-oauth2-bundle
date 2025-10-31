<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
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
    #[Assert\NotBlank(message: 'App ID cannot be empty')]
    #[Assert\Length(max: 255, maxMessage: 'App ID cannot be longer than {{ limit }} characters')]
    private string $appId;

    #[ORM\Column(type: Types::STRING, length: 500, options: ['comment' => '微博应用密钥'])]
    #[Assert\NotBlank(message: 'App Secret cannot be empty')]
    #[Assert\Length(max: 500, maxMessage: 'App Secret cannot be longer than {{ limit }} characters')]
    private string $appSecret;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => 'OAuth2授权范围'])]
    #[Assert\Length(max: 500, maxMessage: 'Scope cannot be longer than {{ limit }} characters')]
    private ?string $scope = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['comment' => '是否启用此配置', 'default' => true])]
    #[Assert\NotNull(message: 'Valid status cannot be null')]
    private bool $valid = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppId(): string
    {
        return $this->appId;
    }

    public function setAppId(string $appId): void
    {
        $this->appId = $appId;
    }

    public function getAppSecret(): string
    {
        return $this->appSecret;
    }

    public function setAppSecret(string $appSecret): void
    {
        $this->appSecret = $appSecret;
    }

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): void
    {
        $this->scope = $scope;
    }

    public function isValid(): bool
    {
        return $this->valid;
    }

    public function setValid(bool $valid): void
    {
        $this->valid = $valid;
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
    public function setIsActive(bool $isActive): void
    {
        $this->valid = $isActive;
    }

    public function __toString(): string
    {
        $appId = isset($this->appId) ? $this->appId : '';

        return sprintf('SinaWeiboOAuth2Config[%s]', $appId);
    }
}
