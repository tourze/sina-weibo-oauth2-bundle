<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Integration\Repository;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2StateRepository;
use Tourze\SinaWeiboOAuth2Bundle\Tests\TestKernel;

class SinaWeiboOAuth2StateRepositoryTest extends KernelTestCase
{
    private SinaWeiboOAuth2StateRepository $repository;

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(SinaWeiboOAuth2StateRepository::class);
        $this->setupDatabase();
    }

    private function setupDatabase(): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($em);
        
        $classes = [
            $em->getClassMetadata(SinaWeiboOAuth2Config::class),
            $em->getClassMetadata(SinaWeiboOAuth2State::class)
        ];
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
    }

    public function testFindValidState(): void
    {
        $state = $this->repository->findValidState('invalid_state');
        $this->assertNull($state);
    }

    public function testCleanupExpiredStates(): void
    {
        $count = $this->repository->cleanupExpiredStates();
        $this->assertGreaterThanOrEqual(0, $count);
    }
}