<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;

/**
 * @extends ServiceEntityRepository<SinaWeiboOAuth2User>
 */
#[AsRepository(entityClass: SinaWeiboOAuth2User::class)]
class SinaWeiboOAuth2UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SinaWeiboOAuth2User::class);
    }

    public function findByUid(string $uid): ?SinaWeiboOAuth2User
    {
        return $this->createQueryBuilder('u')
            ->where('u.uid = :uid')
            ->setParameter('uid', $uid)
            ->orderBy('u.createTime', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByUidAndConfigForUpdate(string $uid, SinaWeiboOAuth2Config $config): ?SinaWeiboOAuth2User
    {
        return $this->createQueryBuilder('u')
            ->where('u.uid = :uid')
            ->andWhere('u.config = :config')
            ->setParameter('uid', $uid)
            ->setParameter('config', $config)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    public function findByUidAndConfig(string $uid, SinaWeiboOAuth2Config $config): ?SinaWeiboOAuth2User
    {
        return $this->createQueryBuilder('u')
            ->where('u.uid = :uid')
            ->andWhere('u.config = :config')
            ->setParameter('uid', $uid)
            ->setParameter('config', $config)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return SinaWeiboOAuth2User[]
     */
    public function findExpiredTokenUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.tokenExpireTime <= :now')
            ->andWhere('u.refreshToken IS NOT NULL')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return SinaWeiboOAuth2User[]
     */
    public function findUsersWithValidTokens(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.tokenExpireTime > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('u.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @return SinaWeiboOAuth2User[]
     */
    public function getUsersByConfig(SinaWeiboOAuth2Config $config): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.config = :config')
            ->setParameter('config', $config)
            ->orderBy('u.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param string[] $uids
     * @return SinaWeiboOAuth2User[]
     */
    public function getUsersByUids(array $uids): array
    {
        if ([] === $uids) {
            return [];
        }

        return $this->createQueryBuilder('u')
            ->where('u.uid IN (:uids)')
            ->setParameter('uids', $uids)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * @param array<string, mixed> $userData
     */
    public function updateOrCreate(string $uid, SinaWeiboOAuth2Config $config, array $userData): SinaWeiboOAuth2User
    {
        $user = $this->findByUidAndConfig($uid, $config);

        if (null === $user) {
            // Create new user with required parameters
            $user = new SinaWeiboOAuth2User();
            $user->setUid($uid);
            $user->setAccessToken($userData['accessToken'] ?? '');
            $user->setExpiresIn($userData['expiresIn'] ?? 0);
            $user->setConfig($config);
        }

        // Update user data
        foreach ($userData as $property => $value) {
            $setter = 'set' . ucfirst($property);
            if (method_exists($user, $setter)) {
                match ($setter) {
                    'setAccessToken' => $user->setAccessToken($value),
                    'setRefreshToken' => $user->setRefreshToken($value),
                    'setExpiresIn' => $user->setExpiresIn($value),
                    'setNickname' => $user->setNickname($value),
                    'setAvatar' => $user->setAvatar($value),
                    'setGender' => $user->setGender($value),
                    'setLocation' => $user->setLocation($value),
                    'setDescription' => $user->setDescription($value),
                    'setRawData' => $user->setRawData($value),
                    default => null,
                };
            }
        }

        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();

        return $user;
    }

    public function save(SinaWeiboOAuth2User $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SinaWeiboOAuth2User $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
