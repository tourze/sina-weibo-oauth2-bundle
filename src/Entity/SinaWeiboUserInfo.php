<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\SinaWeiboOAuth2Bundle\Enum\Gender;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboUserInfoRepository;

/**
 * 新浪微博用户信息实体
 * 用于存储从微博API获取的用户基本信息
 */
#[ORM\Entity(repositoryClass: SinaWeiboUserInfoRepository::class)]
#[ORM\Table(name: 'sina_weibo_user_info', options: ['comment' => '新浪微博用户信息表'])]
class SinaWeiboUserInfo implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[IndexColumn]
    #[ORM\Column(type: 'string', length: 50, nullable: false, unique: true, options: ['comment' => '微博用户UID'])]
    private ?string $weiboUid = null;

    #[ORM\Column(type: 'string', length: 100, nullable: false, options: ['comment' => '用户昵称'])]
    private ?string $name = null;

    #[IndexColumn]
    #[ORM\Column(type: 'string', length: 100, nullable: false, options: ['comment' => '用户显示名称'])]
    private ?string $screenName = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true, options: ['comment' => '用户头像URL'])]
    private ?string $profileImageUrl = null;

    #[ORM\Column(type: 'string', length: 1, nullable: true, enumType: Gender::class, options: ['comment' => '性别'])]
    private ?Gender $gender = null;

    #[ORM\Column(type: 'string', length: 200, nullable: true, options: ['comment' => '所在地'])]
    private ?string $location = null;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => '个人描述'])]
    private ?string $description = null;

    #[ORM\Column(type: 'string', length: 500, nullable: true, options: ['comment' => '个人网址'])]
    private ?string $url = null;

    #[ORM\Column(type: 'integer', nullable: false, options: ['comment' => '粉丝数量'])]
    private int $followersCount = 0;

    #[ORM\Column(type: 'integer', nullable: false, options: ['comment' => '关注数量'])]
    private int $friendsCount = 0;

    #[ORM\Column(type: 'integer', nullable: false, options: ['comment' => '微博数量'])]
    private int $statusesCount = 0;

    #[IndexColumn]
    #[ORM\Column(type: 'boolean', nullable: false, options: ['comment' => '是否认证用户'])]
    private bool $verified = false;

    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => '认证类型'])]
    private ?int $verifiedType = null;

    #[ORM\Column(type: 'integer', nullable: true, options: ['comment' => '用户等级'])]
    private ?int $userLevel = null;

    #[ORM\Column(type: 'datetime', nullable: true, options: ['comment' => '微博账号创建时间'])]
    private ?\DateTime $weiboCreateTime = null;

    #[ORM\Column(type: 'json', nullable: true, options: ['comment' => '原始API数据'])]
    private ?array $rawData = null;

    #[CreateTimeColumn]
    #[ORM\Column(type: 'datetime', nullable: false, options: ['comment' => '创建时间'])]
    private ?\DateTime $createTime = null;

    #[UpdateTimeColumn]
    #[ORM\Column(type: 'datetime', nullable: false, options: ['comment' => '更新时间'])]
    private ?\DateTime $updateTime = null;

    public function __toString(): string
    {
        return $this->screenName ?? $this->name ?? "WeiboUser#{$this->weiboUid}";
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getScreenName(): ?string
    {
        return $this->screenName;
    }

    public function setScreenName(string $screenName): static
    {
        $this->screenName = $screenName;
        return $this;
    }

    public function getProfileImageUrl(): ?string
    {
        return $this->profileImageUrl;
    }

    public function setProfileImageUrl(?string $profileImageUrl): static
    {
        $this->profileImageUrl = $profileImageUrl;
        return $this;
    }

    public function getGender(): ?Gender
    {
        return $this->gender;
    }

    public function setGender(?Gender $gender): static
    {
        $this->gender = $gender;
        return $this;
    }

    public function getLocation(): ?string
    {
        return $this->location;
    }

    public function setLocation(?string $location): static
    {
        $this->location = $location;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function getFollowersCount(): int
    {
        return $this->followersCount;
    }

    public function setFollowersCount(int $followersCount): static
    {
        $this->followersCount = $followersCount;
        return $this;
    }

    public function getFriendsCount(): int
    {
        return $this->friendsCount;
    }

    public function setFriendsCount(int $friendsCount): static
    {
        $this->friendsCount = $friendsCount;
        return $this;
    }

    public function getStatusesCount(): int
    {
        return $this->statusesCount;
    }

    public function setStatusesCount(int $statusesCount): static
    {
        $this->statusesCount = $statusesCount;
        return $this;
    }

    public function isVerified(): bool
    {
        return $this->verified;
    }

    public function setVerified(bool $verified): static
    {
        $this->verified = $verified;
        return $this;
    }

    public function getVerifiedType(): ?int
    {
        return $this->verifiedType;
    }

    public function setVerifiedType(?int $verifiedType): static
    {
        $this->verifiedType = $verifiedType;
        return $this;
    }

    public function getUserLevel(): ?int
    {
        return $this->userLevel;
    }

    public function setUserLevel(?int $userLevel): static
    {
        $this->userLevel = $userLevel;
        return $this;
    }

    public function getWeiboCreateTime(): ?\DateTime
    {
        return $this->weiboCreateTime;
    }

    public function setWeiboCreateTime(?\DateTime $weiboCreateTime): static
    {
        $this->weiboCreateTime = $weiboCreateTime;
        return $this;
    }

    public function getRawData(): ?array
    {
        return $this->rawData;
    }

    public function setRawData(?array $rawData): static
    {
        $this->rawData = $rawData;
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
     * 从API响应数据填充实体
     */
    public function fillFromApiResponse(array $data): static
    {
        $this->setWeiboUid((string)$data['id'])
            ->setName($data['name'] ?? '')
            ->setScreenName($data['screen_name'] ?? '')
            ->setProfileImageUrl($data['profile_image_url'] ?? null)
            ->setGender(Gender::fromString($data['gender'] ?? null))
            ->setLocation($data['location'] ?? null)
            ->setDescription($data['description'] ?? null)
            ->setUrl($data['url'] ?? null)
            ->setFollowersCount($data['followers_count'] ?? 0)
            ->setFriendsCount($data['friends_count'] ?? 0)
            ->setStatusesCount($data['statuses_count'] ?? 0)
            ->setVerified($data['verified'] ?? false)
            ->setVerifiedType($data['verified_type'] ?? null)
            ->setUserLevel($data['user_level'] ?? null)
            ->setRawData($data);

        if (isset($data['created_at'])) {
            try {
                $this->setWeiboCreateTime(new \DateTime($data['created_at']));
            } catch (\Exception $e) {
                // 忽略日期解析错误
            }
        }

        return $this;
    }
}
