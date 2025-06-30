<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Integration\Repository;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2UserRepository;
use Tourze\SinaWeiboOAuth2Bundle\Tests\TestKernel;

class SinaWeiboOAuth2UserRepositoryTest extends KernelTestCase
{
    private SinaWeiboOAuth2UserRepository $repository;

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->repository = self::getContainer()->get(SinaWeiboOAuth2UserRepository::class);
        $this->setupDatabase();
    }

    private function setupDatabase(): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($em);
        
        $classes = [
            $em->getClassMetadata(SinaWeiboOAuth2Config::class),
            $em->getClassMetadata(SinaWeiboOAuth2User::class)
        ];
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
    }

    public function testFindByUid(): void
    {
        $user = $this->repository->findByUid('nonexistent_uid');
        $this->assertNull($user);
    }

    public function testFindExpiredTokenUsers(): void
    {
        $users = $this->repository->findExpiredTokenUsers();
        $this->assertEmpty($users);
    }

    public function testUpdateOrCreateWithInvalidData(): void
    {
        $config = new SinaWeiboOAuth2Config();
        
        $this->expectException(\Tourze\SinaWeiboOAuth2Bundle\Exception\InvalidUserDataException::class);
        $this->repository->updateOrCreate([], $config);
    }
}