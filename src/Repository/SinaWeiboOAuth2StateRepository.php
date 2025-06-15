<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;

/**
 * @method SinaWeiboOAuth2State|null find($id, $lockMode = null, $lockVersion = null)
 * @method SinaWeiboOAuth2State|null findOneBy(array $criteria, array $orderBy = null)
 * @method SinaWeiboOAuth2State[] findAll()
 * @method SinaWeiboOAuth2State[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SinaWeiboOAuth2StateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SinaWeiboOAuth2State::class);
    }

    public function findValidState(string $state): ?SinaWeiboOAuth2State
    {
        return $this->createQueryBuilder('s')
            ->andWhere('s.state = :state')
            ->andWhere('s.used = :used')
            ->andWhere('s.expireTime > :now')
            ->setParameter('state', $state)
            ->setParameter('used', false)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function cleanupExpiredStates(): int
    {
        $qb = $this->createQueryBuilder('s')
            ->delete()
            ->where('s.expireTime <= :now OR s.used = :used')
            ->setParameter('now', new \DateTime())
            ->setParameter('used', true);

        return $qb->getQuery()->execute();
    }

    public function countActiveStates(): int
    {
        return $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.used = :used')
            ->andWhere('s.expireTime > :now')
            ->setParameter('used', false)
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function findStatesBySession(string $sessionId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('s.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }
}