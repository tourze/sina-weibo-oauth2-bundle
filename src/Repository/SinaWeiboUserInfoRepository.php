<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboUserInfo;

/**
 * 新浪微博用户信息Repository
 *
 * @method SinaWeiboUserInfo|null find($id, $lockMode = null, $lockVersion = null)
 * @method SinaWeiboUserInfo|null findOneBy(array $criteria, array $orderBy = null)
 * @method SinaWeiboUserInfo[]    findAll()
 * @method SinaWeiboUserInfo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SinaWeiboUserInfoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SinaWeiboUserInfo::class);
    }

    /**
     * 根据微博UID查找用户信息
     */
    public function findByWeiboUid(string $weiboUid): ?SinaWeiboUserInfo
    {
        return $this->findOneBy(['weiboUid' => $weiboUid]);
    }

    /**
     * 根据显示名称查找用户信息
     */
    public function findByScreenName(string $screenName): ?SinaWeiboUserInfo
    {
        return $this->findOneBy(['screenName' => $screenName]);
    }

    /**
     * 查找认证用户
     */
    public function findVerifiedUsers(): array
    {
        return $this->findBy(['verified' => true]);
    }

    /**
     * 根据关键词搜索用户（在昵称和显示名称中搜索）
     */
    public function searchByKeyword(string $keyword, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.name LIKE :keyword')
            ->orWhere('u.screenName LIKE :keyword')
            ->setParameter('keyword', '%' . $keyword . '%')
            ->orderBy('u.followersCount', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * 获取粉丝数量排行榜
     */
    public function getTopUsersByFollowers(int $limit = 10): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.followersCount > 0')
            ->orderBy('u.followersCount', 'DESC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * 查找需要更新的用户信息（超过指定时间未更新）
     */
    public function findUsersNeedUpdate(\DateTime $beforeDate, int $limit = 100): array
    {
        $qb = $this->createQueryBuilder('u')
            ->where('u.updateTime < :beforeDate')
            ->setParameter('beforeDate', $beforeDate)
            ->orderBy('u.updateTime', 'ASC')
            ->setMaxResults($limit);

        return $qb->getQuery()->getResult();
    }

    /**
     * 保存或更新用户信息
     */
    public function saveOrUpdate(SinaWeiboUserInfo $userInfo): SinaWeiboUserInfo
    {
        $existingUser = $this->findByWeiboUid($userInfo->getWeiboUid());

        if ($existingUser) {
            // 更新现有用户信息
            $existingUser->setName($userInfo->getName())
                ->setScreenName($userInfo->getScreenName())
                ->setProfileImageUrl($userInfo->getProfileImageUrl())
                ->setGender($userInfo->getGender())
                ->setLocation($userInfo->getLocation())
                ->setDescription($userInfo->getDescription())
                ->setUrl($userInfo->getUrl())
                ->setFollowersCount($userInfo->getFollowersCount())
                ->setFriendsCount($userInfo->getFriendsCount())
                ->setStatusesCount($userInfo->getStatusesCount())
                ->setVerified($userInfo->isVerified())
                ->setVerifiedType($userInfo->getVerifiedType())
                ->setUserLevel($userInfo->getUserLevel())
                ->setWeiboCreateTime($userInfo->getWeiboCreateTime())
                ->setRawData($userInfo->getRawData())
                ->setUpdateTime(new \DateTime());

            return $existingUser;
        } else {
            // 保存新用户信息
            $this->getEntityManager()->persist($userInfo);
            return $userInfo;
        }
    }
}
