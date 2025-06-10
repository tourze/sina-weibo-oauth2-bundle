<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboAppConfig;

/**
 * 新浪微博应用配置Repository
 *
 * @method SinaWeiboAppConfig|null find($id, $lockMode = null, $lockVersion = null)
 * @method SinaWeiboAppConfig|null findOneBy(array $criteria, array $orderBy = null)
 * @method SinaWeiboAppConfig[]    findAll()
 * @method SinaWeiboAppConfig[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SinaWeiboAppConfigRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SinaWeiboAppConfig::class);
    }

    /**
     * 根据App Key查找应用配置
     */
    public function findByAppKey(string $appKey): ?SinaWeiboAppConfig
    {
        return $this->findOneBy(['appKey' => $appKey]);
    }

    /**
     * 查找所有有效的应用配置
     */
    public function findAllValid(): array
    {
        return $this->findBy(['valid' => true]);
    }

    /**
     * 根据App Key查找有效的应用配置
     */
    public function findValidByAppKey(string $appKey): ?SinaWeiboAppConfig
    {
        return $this->findOneBy([
            'appKey' => $appKey,
            'valid' => true
        ]);
    }
}
