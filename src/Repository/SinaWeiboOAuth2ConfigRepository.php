<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Cache\CacheItemInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;

/**
 * @method SinaWeiboOAuth2Config|null find($id, $lockMode = null, $lockVersion = null)
 * @method SinaWeiboOAuth2Config|null findOneBy(array $criteria, array $orderBy = null)
 * @method SinaWeiboOAuth2Config[] findAll()
 * @method SinaWeiboOAuth2Config[] findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class SinaWeiboOAuth2ConfigRepository extends ServiceEntityRepository
{
    private const CACHE_KEY_VALID_CONFIG = 'sina_weibo_oauth2_valid_config';
    private const CACHE_TTL = 3600; // 1 hour

    public function __construct(
        ManagerRegistry $registry,
        private ?CacheInterface $cache = null
    ) {
        parent::__construct($registry, SinaWeiboOAuth2Config::class);
    }

    public function findValidConfig(): ?SinaWeiboOAuth2Config
    {
        if (!$this->cache) {
            return $this->findValidConfigFromDatabase();
        }

        return $this->cache->get(self::CACHE_KEY_VALID_CONFIG, function (ItemInterface $item): ?SinaWeiboOAuth2Config {
            $item->expiresAfter(self::CACHE_TTL);
            return $this->findValidConfigFromDatabase();
        });
    }

    private function findValidConfigFromDatabase(): ?SinaWeiboOAuth2Config
    {
        return $this->createQueryBuilder('c')
            ->where('c.valid = :valid')
            ->setParameter('valid', true)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    public function findActiveConfigs(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.valid = :valid')
            ->setParameter('valid', true)
            ->orderBy('c.createdAt', 'DESC')
            ->getQuery()
            ->getResult();
    }

    public function invalidateCache(): void
    {
        if ($this->cache instanceof CacheItemInterface) {
            $this->cache->delete(self::CACHE_KEY_VALID_CONFIG);
        }
    }
}