<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Token;

/**
 * 新浪微博OAuth2令牌Repository
 *
 * @method SinaWeiboOAuth2Token|null find($id, $lockMode = null, $lockVersion = null)
 * @method SinaWeiboOAuth2Token|null findOneBy(array $criteria, array $orderBy = null)
 * @method SinaWeiboOAuth2Token[]    findAll()
 * @method SinaWeiboOAuth2Token[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SinaWeiboOAuth2TokenRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SinaWeiboOAuth2Token::class);
    }

    /**
     * 根据应用配置和微博UID查找令牌
     */
    public function findByAppConfigAndWeiboUid(\Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboAppConfig $appConfig, string $weiboUid): ?SinaWeiboOAuth2Token
    {
        return $this->findOneBy([
            'appConfig' => $appConfig,
            'weiboUid' => $weiboUid
        ]);
    }

    /**
     * 根据用户和应用配置查找令牌
     */
    public function findByUserAndAppConfig(\Symfony\Component\Security\Core\User\UserInterface $user, \Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboAppConfig $appConfig): ?SinaWeiboOAuth2Token
    {
        return $this->findOneBy([
            'user' => $user,
            'appConfig' => $appConfig
        ]);
    }

    /**
     * 查找用户在指定应用下的有效令牌
     */
    public function findValidTokenByAppConfigAndWeiboUid(\Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboAppConfig $appConfig, string $weiboUid): ?SinaWeiboOAuth2Token
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.appConfig = :appConfig')
            ->andWhere('t.weiboUid = :weiboUid')
            ->andWhere('t.valid = :valid')
            ->andWhere('(t.expiresTime IS NULL OR t.expiresTime > :now)')
            ->setParameter('appConfig', $appConfig)
            ->setParameter('weiboUid', $weiboUid)
            ->setParameter('valid', true)
            ->setParameter('now', new \DateTime())
            ->orderBy('t.createTime', 'DESC')
            ->setMaxResults(1);

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * 查找所有过期的令牌
     */
    public function findExpiredTokens(): array
    {
        $qb = $this->createQueryBuilder('t')
            ->where('t.valid = :valid')
            ->andWhere('t.expiresTime IS NOT NULL')
            ->andWhere('t.expiresTime <= :now')
            ->setParameter('valid', true)
            ->setParameter('now', new \DateTime());

        return $qb->getQuery()->getResult();
    }

    /**
     * 批量标记过期令牌为无效
     */
    public function markExpiredTokensAsInvalid(): int
    {
        $qb = $this->createQueryBuilder('t')
            ->update()
            ->set('t.valid', ':invalid')
            ->set('t.updateTime', ':now')
            ->where('t.valid = :valid')
            ->andWhere('t.expiresTime IS NOT NULL')
            ->andWhere('t.expiresTime <= :now')
            ->setParameter('invalid', false)
            ->setParameter('valid', true)
            ->setParameter('now', new \DateTime());

        return $qb->getQuery()->execute();
    }

    /**
     * 删除指定时间之前的无效令牌
     */
    public function deleteInvalidTokensBefore(\DateTime $beforeDate): int
    {
        $qb = $this->createQueryBuilder('t')
            ->delete()
            ->where('t.valid = :valid')
            ->andWhere('t.updateTime < :beforeDate')
            ->setParameter('valid', false)
            ->setParameter('beforeDate', $beforeDate);

        return $qb->getQuery()->execute();
    }
}
