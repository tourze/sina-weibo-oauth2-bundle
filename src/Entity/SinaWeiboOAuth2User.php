<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
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
    #[Assert\NotBlank(message: 'UID cannot be empty')]
    #[Assert\Length(max: 255, maxMessage: 'UID cannot be longer than {{ limit }} characters')]
    private string $uid;

    #[ORM\Column(type: Types::TEXT, options: ['comment' => '访问令牌'])]
    #[Assert\NotBlank(message: 'Access token cannot be empty')]
    #[Assert\Length(max: 65535, maxMessage: 'Access token cannot be longer than {{ limit }} characters')]
    private string $accessToken;

    #[IndexColumn]
    #[ORM\Column(name: 'token_expire_time', type: Types::DATETIME_IMMUTABLE, options: ['comment' => '令牌过期时间'])]
    #[Assert\NotNull(message: 'Token expire time cannot be null')]
    private \DateTimeImmutable $tokenExpireTime;

    #[ORM\Column(name: 'refresh_token', type: Types::TEXT, nullable: true, options: ['comment' => '刷新令牌'])]
    #[Assert\Length(max: 65535, maxMessage: 'Refresh token cannot be longer than {{ limit }} characters')]
    private ?string $refreshToken = null;

    #[ORM\ManyToOne(targetEntity: SinaWeiboOAuth2Config::class, cascade: ['persist'])]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Config cannot be null')]
    private ?SinaWeiboOAuth2Config $config = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true, options: ['comment' => '昵称'])]
    #[Assert\Length(max: 255, maxMessage: 'Nickname cannot be longer than {{ limit }} characters')]
    private ?string $nickname = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '头像URL'])]
    #[Assert\Length(max: 500, maxMessage: 'Avatar URL cannot be longer than {{ limit }} characters')]
    #[Assert\Url(message: 'Avatar must be a valid URL')]
    private ?string $avatar = null;

    #[ORM\Column(type: Types::STRING, length: 10, nullable: true, options: ['comment' => '性别'])]
    #[Assert\Length(max: 10, maxMessage: 'Gender cannot be longer than {{ limit }} characters')]
    private ?string $gender = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true, options: ['comment' => '地理位置'])]
    #[Assert\Length(max: 100, maxMessage: 'Location cannot be longer than {{ limit }} characters')]
    private ?string $location = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true, options: ['comment' => '个人描述'])]
    #[Assert\Length(max: 500, maxMessage: 'Description cannot be longer than {{ limit }} characters')]
    private ?string $description = null;

    /**
     * @var array<string, mixed>|null
     */
    #[ORM\Column(name: 'raw_data', type: Types::JSON, nullable: true, options: ['comment' => '原始API响应数据'])]
    #[Assert\Type(type: 'array', message: 'Raw data must be an array')]
    private ?array $rawData = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUid(): string
    {
        return $this->uid;
    }

    public function setUid(string $uid): void
    {
        $this->uid = $uid;
    }

    public function getAccessToken(): string
    {
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken): void
    {
        $this->accessToken = $accessToken;
    }

    public function getTokenExpireTime(): \DateTimeImmutable
    {
        return $this->tokenExpireTime;
    }

    public function setTokenExpireTime(\DateTimeImmutable $tokenExpireTime): void
    {
        $this->tokenExpireTime = $tokenExpireTime;
    }

    public function setExpiresIn(int $expiresIn): void
    {
        if ($expiresIn >= 0) {
            $this->tokenExpireTime = new \DateTimeImmutable(sprintf('+%d seconds', $expiresIn));
        } else {
            $this->tokenExpireTime = new \DateTimeImmutable(sprintf('%d seconds', $expiresIn));
        }
    }

    public function getRefreshToken(): ?string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(?string $refreshToken): void
    {
        $this->refreshToken = $refreshToken;
    }

    public function getConfig(): ?SinaWeiboOAuth2Config
    {
        return $this->config;
    }

    public function setConfig(SinaWeiboOAuth2Config $config): void
    {
        $this->config = $config;
    }

    public function getNickname(): ?string
    {
        return $this->nickname;
    }

    public function setNickname(?string $nickname): void
    {
        $this->nickname = $nickname;
    }

    public function getAvatar(): ?string
    {
        return $this->avatar;
    }

    public function setAvatar(?string $avatar): void
    {
        $this->avatar = $avatar;
    }

    public function getGender(): ?string
    {
        return $this->gender;
    }

    public function setGender(?string $gender): void
    {
        $this->gender = $gender;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): void
    {
        $this->location = $location;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
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
    public function setRawData(?array $rawData): void
    {
        $this->rawData = $rawData;
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
