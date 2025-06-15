<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;

/**
 * @method SinaWeiboOAuth2User|null find($id, $lockMode = null, $lockVersion = null)
 * @method SinaWeiboOAuth2User|null findOneBy(array $criteria, array $orderBy = null)
 * @method SinaWeiboOAuth2User[] findAll()
 * @method SinaWeiboOAuth2User[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
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
            ->orderBy('u.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function updateOrCreate(array $data, SinaWeiboOAuth2Config $config): SinaWeiboOAuth2User
    {
        $uid = $data['uid'] ?? $data['id'] ?? null;
        if (!$uid) {
            throw new \InvalidArgumentException('User data must contain uid or id field');
        }

        $user = $this->findByUidAndConfig($uid, $config);

        if (!$user) {
            $expiresIn = (int)($data['expires_in'] ?? 3600);
            $user = new SinaWeiboOAuth2User($uid, $data['access_token'], $expiresIn, $config);
        } else {
            $user->setAccessToken($data['access_token']);
            if (isset($data['expires_in'])) {
                $user->setExpiresIn((int)$data['expires_in']);
            }
        }

        if (isset($data['refresh_token'])) {
            $user->setRefreshToken($data['refresh_token']);
        }

        // Update user profile data if available
        if (isset($data['screen_name'])) {
            $user->setNickname($data['screen_name']);
        }
        if (isset($data['name'])) {
            $user->setNickname($data['name']);
        }
        if (isset($data['profile_image_url'])) {
            $user->setAvatar($data['profile_image_url']);
        }
        if (isset($data['avatar_large'])) {
            $user->setAvatar($data['avatar_large']);
        }
        if (isset($data['gender'])) {
            $user->setGender($data['gender']);
        }
        if (isset($data['location'])) {
            $user->setLocation($data['location']);
        }
        if (isset($data['description'])) {
            $user->setDescription($data['description']);
        }

        $user->setRawData($data);

        return $user;
    }

    public function findByUidAndConfig(string $uid, SinaWeiboOAuth2Config $config): ?SinaWeiboOAuth2User
    {
        return $this->createQueryBuilder('u')
            ->where('u.uid = :uid')
            ->andWhere('u.config = :config')
            ->setParameter('uid', $uid)
            ->setParameter('config', $config)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findExpiredTokenUsers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.tokenExpireTime <= :now')
            ->andWhere('u.refreshToken IS NOT NULL')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getResult();
    }

    public function findUsersWithValidTokens(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.tokenExpireTime > :now')
            ->setParameter('now', new \DateTime())
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getUsersByConfig(SinaWeiboOAuth2Config $config): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.config = :config')
            ->setParameter('config', $config)
            ->orderBy('u.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function getUsersByUids(array $uids): array
    {
        if (empty($uids)) {
            return [];
        }

        return $this->getEntityManager()->createQueryBuilder()
            ->select('u')
            ->from(SinaWeiboOAuth2User::class, 'u')
            ->where('u.uid IN (:uids)')
            ->setParameter('uids', $uids)
            ->getQuery()
            ->getResult();
    }

}