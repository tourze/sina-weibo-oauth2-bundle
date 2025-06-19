<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2UserRepository;

#[ORM\Entity(repositoryClass: SinaWeiboOAuth2UserRepository::class)]
#[ORM\Table(name: 'sina_weibo_oauth2_user', options: ['comment' => '新浪微博OAuth2用户表'])]
#[ORM\UniqueConstraint(name: 'unique_uid_config', columns: ['uid', 'config_id'])]
class SinaWeiboOAuth2User implements \Stringable
{
    use TimestampableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '主键ID'])]
    private ?int $id = null;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 255, options: ['comment' => '微博用户唯一标识'])]
    private string $uid;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '访问令牌'])]
    private string $accessToken;

    #[IndexColumn]
    #[ORM\Column(name: 'token_expire_time', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '令牌过期时间'])]
    private \DateTimeImmutable $tokenExpireTime;

    #[ORM\Column(name: 'refresh_token', type: Types::TEXT, nullable: true, options: ['comment' => '刷新令牌'])]
    private ?string $refreshToken = null;

    #[ORM\ManyToOne(targetEntity: SinaWeiboOAuth2Config::class)]
    #[ORM\JoinColumn(nullable: false)]
    private SinaWeiboOAuth2Config $config;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '昵称'])]
    private ?string $nickname = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '头像URL'])]
    private ?string $avatar = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '性别'])]
    private ?string $gender = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '地理位置'])]
    private ?string $location = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '个人描述'])]
    private ?string $description = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'raw_data', type: Types::JSON, nullable: true, options: ['comment' => '原始API响应数据'])]
    private ?array $rawData = null;

    public function __construct(string $uid, string $accessToken, int $expiresIn, SinaWeiboOAuth2Config $config)
    {
        $this->uid = $uid;
        $this->accessToken = $accessToken;
        $this->tokenExpireTime = new \DateTimeImmutable(sprintf('+%d seconds', $expiresIn));
        $this->config = $config;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): self
    {
        $this->accessToken = $accessToken;
        return $this;
    }

    public function getTokenExpireTime(): \DateTimeImmutable
    {
        return $this->tokenExpireTime;
    }

    public function setExpiresIn(int $expiresIn): self
    {
        $this->tokenExpireTime = new \DateTimeImmutable(sprintf('+%d seconds', $expiresIn));
        return $this;
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;
        return $this;
    }

    public function getConfig(): SinaWeiboOAuth2Config
    {
        return $this->config;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): self
    {
        $this->nickname = $nickname;
        return $this;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): self
    {
        $this->avatar = $avatar;
        return $this;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): self
    {
        $this->gender = $gender;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): self
    {
        $this->location = $location;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRawData(): ?array
    {
        return $this->rawData;
    }

    /**
     * @param array<string, mixed>|null $rawData
     */
    public function setRawData(?array $rawData): self
    {
        $this->rawData = $rawData;
        return $this;
    }

    public function isTokenExpired(): bool
    {
        return $this->tokenExpireTime <= new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('SinaWeiboOAuth2User[%s:%s]', $this->uid, $this->nickname ?? 'unknown');
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