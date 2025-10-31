<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Psr\Container\ContainerInterface as PsrContainerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;

/**
 * @extends ServiceEntityRepository<SinaWeiboOAuth2Config>
 */
#[AsRepository(entityClass: SinaWeiboOAuth2Config::class)]
class SinaWeiboOAuth2ConfigRepository extends ServiceEntityRepository
{
    private const CACHE_KEY_VALID_CONFIG = 'sina_weibo_oauth2_valid_config';
    private const CACHE_TTL = 3600; // 1 hour

    /**
     * 说明：避免在仓库构造时直接注入 CacheInterface，
     * 防止在测试环境中过早初始化全局的 \"cache.app\" 服务，导致 TestContainer 无法替换。
     * 因此改为在首次使用时从容器延迟解析，允许测试覆盖缓存实现。
     */
    public function __construct(
        ManagerRegistry $registry,
        private ?PsrContainerInterface $container = null,
    ) {
        parent::__construct($registry, SinaWeiboOAuth2Config::class);
    }

    private ?CacheInterface $cache = null;

    private function getCache(): ?CacheInterface
    {
        if (null !== $this->cache) {
            return $this->cache;
        }

        if (null !== $this->container && $this->container->has(CacheInterface::class)) {
            $service = $this->container->get(CacheInterface::class);
            if ($service instanceof CacheInterface) {
                $this->cache = $service;
            }
        }

        return $this->cache;
    }

    public function setCache(CacheInterface $cache): void
    {
        $this->cache = $cache;
    }

    public function findValidConfig(): ?SinaWeiboOAuth2Config
    {
        $cache = $this->getCache();

        if (null === $cache) {
            return $this->findValidConfigFromDatabase();
        }

        return $cache->get(self::CACHE_KEY_VALID_CONFIG, function (ItemInterface $item): ?SinaWeiboOAuth2Config {
            $item->expiresAfter(self::CACHE_TTL);

            return $this->findValidConfigFromDatabase();
        });
    }

    private function findValidConfigFromDatabase(): ?SinaWeiboOAuth2Config
    {
        return $this->createQueryBuilder('c')
            ->where('c.valid = :valid')
            ->setParameter('valid', true)
            ->orderBy('c.createTime', 'DESC')
            ->addOrderBy('c.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    /**
     * @return SinaWeiboOAuth2Config[]
     */
    public function findActiveConfigs(): array
    {
        return $this->createQueryBuilder('c')
            ->where('c.valid = :valid')
            ->setParameter('valid', true)
            ->orderBy('c.createTime', 'DESC')
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(SinaWeiboOAuth2Config $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SinaWeiboOAuth2Config $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);
        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function invalidateCache(): void
    {
        $cache = $this->getCache();
        if (null !== $cache) {
            $cache->delete(self::CACHE_KEY_VALID_CONFIG);
        }
    }
}
