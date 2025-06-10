<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Attribute\CreateTimeColumn;
use Tourze\DoctrineTimestampBundle\Attribute\UpdateTimeColumn;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboAppConfigRepository;

/**
 * 新浪微博应用配置实体
 * 用于存储微博应用的配置信息，包括App Key、App Secret等
 */
#[ORM\Entity(repositoryClass: SinaWeiboAppConfigRepository::class)]
#[ORM\Table(name: 'sina_weibo_app_config', options: ['comment' => '新浪微博应用配置表'])]
class SinaWeiboAppConfig implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[IndexColumn]
    #[ORM\Column(type: 'string', length: 100, nullable: false, options: ['comment' => '应用名称'])]
    private ?string $appName = null;

    #[IndexColumn]
    #[ORM\Column(type: 'string', length: 255, nullable: false, unique: true, options: ['comment' => '应用Key(Client ID)'])]
    private ?string $appKey = null;

    #[ORM\Column(type: 'text', nullable: false, options: ['comment' => '应用Secret'])]
    private ?string $appSecret = null;

    #[ORM\Column(type: 'string', length: 500, nullable: false, options: ['comment' => '回调地址'])]
    private ?string $redirectUri = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true, options: ['comment' => '权限范围'])]
    private ?string $scope = null;

    #[IndexColumn]
    #[ORM\Column(type: 'boolean', nullable: false, options: ['comment' => '是否有效'])]
    private bool $valid = true;

    #[ORM\Column(type: 'text', nullable: true, options: ['comment' => '备注信息'])]
    private ?string $remark = null;

    #[CreateTimeColumn]
    #[ORM\Column(type: 'datetime', nullable: false, options: ['comment' => '创建时间'])]
    private ?\DateTime $createTime = null;

    #[UpdateTimeColumn]
    #[ORM\Column(type: 'datetime', nullable: false, options: ['comment' => '更新时间'])]
    private ?\DateTime $updateTime = null;

    public function __toString(): string
    {
        return $this->appName ?? 'SinaWeiboApp#' . ($this->id ?? 'new');
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAppName(): ?string
    {
        return $this->appName;
    }

    public function setAppName(string $appName): static
    {
        $this->appName = $appName;
        return $this;
    }

    public function getAppKey(): ?string
    {
        return $this->appKey;
    }

    public function setAppKey(string $appKey): static
    {
        $this->appKey = $appKey;
        return $this;
    }

    public function getAppSecret(): ?string
    {
        return $this->appSecret;
    }

    public function setAppSecret(string $appSecret): static
    {
        $this->appSecret = $appSecret;
        return $this;
    }

    public function getRedirectUri(): ?string
    {
        return $this->redirectUri;
    }

    public function setRedirectUri(string $redirectUri): static
    {
        $this->redirectUri = $redirectUri;
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

    public function getRemark(): ?string
    {
        return $this->remark;
    }

    public function setRemark(?string $remark): static
    {
        $this->remark = $remark;
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
}
