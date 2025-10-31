<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;

/**
 * @extends ServiceEntityRepository<SinaWeiboOAuth2State>
 */
#[AsRepository(entityClass: SinaWeiboOAuth2State::class)]
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
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function cleanupExpiredStates(): int
    {
        $qb = $this->createQueryBuilder('s')
            ->delete()
            ->where('s.expireTime <= :now OR s.used = :used')
            ->setParameter('now', new \DateTimeImmutable())
            ->setParameter('used', true)
        ;

        return $qb->getQuery()->execute();
    }

    public function countActiveStates(): int
    {
        $result = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.used = :used')
            ->andWhere('s.expireTime > :now')
            ->setParameter('used', false)
            ->setParameter('now', new \DateTimeImmutable())
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * @return SinaWeiboOAuth2State[]
     */
    public function findStatesBySession(string $sessionId): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->orderBy('s.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(SinaWeiboOAuth2State $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SinaWeiboOAuth2State $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
