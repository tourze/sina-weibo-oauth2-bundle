<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Repository;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2ConfigRepository;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2StateRepository;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2UserRepository;
use Tourze\SinaWeiboOAuth2Bundle\Tests\TestKernel;

class SinaWeiboOAuth2RepositoryTest extends KernelTestCase
{
    private SinaWeiboOAuth2ConfigRepository $configRepository;
    private SinaWeiboOAuth2StateRepository $stateRepository;
    private SinaWeiboOAuth2UserRepository $userRepository;
    private $entityManager;

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testConfigRepositoryFindValidConfig(): void
    {
        // Create active config first
        $activeConfig = new SinaWeiboOAuth2Config();
        $activeConfig->setAppId('active_app')
            ->setAppSecret('active_secret')
            ->setValid(true);
        $this->persistAndFlush($activeConfig);
        
        // Add a delay to ensure timestamp difference
        usleep(50000); // 50ms

        // Create inactive config
        $inactiveConfig = new SinaWeiboOAuth2Config();
        $inactiveConfig->setAppId('inactive_app')
            ->setAppSecret('inactive_secret')
            ->setValid(false);
        $this->persistAndFlush($inactiveConfig);
        
        // Add another delay to ensure newer timestamp
        usleep(50000); // 50ms
        
        // Create another active config (newer)
        $newerActiveConfig = new SinaWeiboOAuth2Config();
        $newerActiveConfig->setAppId('new_app_id')
            ->setAppSecret('new_secret')
            ->setValid(true);
        $this->persistAndFlush($newerActiveConfig);

        // Clear cache to ensure fresh data is retrieved
        $this->configRepository->invalidateCache();
        
        $result = $this->configRepository->findValidConfig();

        $this->assertNotNull($result);
        $this->assertEquals('new_app_id', $result->getAppId());
        $this->assertTrue($result->isActive());
    }

    private function persistAndFlush($entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function testConfigRepositoryFindActiveConfigs(): void
    {
        // Create multiple configs
        $config1 = new SinaWeiboOAuth2Config();
        $config1->setAppId('app1')->setAppSecret('secret1')->setValid(true);
        $this->persistAndFlush($config1);

        $config2 = new SinaWeiboOAuth2Config();
        $config2->setAppId('app2')->setAppSecret('secret2')->setValid(false);
        $this->persistAndFlush($config2);

        $config3 = new SinaWeiboOAuth2Config();
        $config3->setAppId('app3')->setAppSecret('secret3')->setValid(true);
        $this->persistAndFlush($config3);

        $results = $this->configRepository->findActiveConfigs();

        $this->assertCount(2, $results);
        $this->assertTrue($results[0]->isActive());
        $this->assertTrue($results[1]->isActive());
    }

    public function testStateRepositoryFindValidState(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app')->setAppSecret('test_secret');
        $this->persistAndFlush($config);

        // Create expired state
        $expiredState = new SinaWeiboOAuth2State('expired_state', $config, -1);
        $this->persistAndFlush($expiredState);

        // Create used state
        $usedState = new SinaWeiboOAuth2State('used_state', $config);
        $usedState->markAsUsed();
        $this->persistAndFlush($usedState);

        // Create valid state
        $validState = new SinaWeiboOAuth2State('valid_state', $config);
        $this->persistAndFlush($validState);

        $result = $this->stateRepository->findValidState('valid_state');
        $this->assertNotNull($result);
        $this->assertEquals('valid_state', $result->getState());

        $expiredResult = $this->stateRepository->findValidState('expired_state');
        $this->assertNull($expiredResult);

        $usedResult = $this->stateRepository->findValidState('used_state');
        $this->assertNull($usedResult);
    }

    public function testStateRepositoryCleanupExpiredStates(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app')->setAppSecret('test_secret');
        $this->persistAndFlush($config);

        // Create expired state
        $expiredState = new SinaWeiboOAuth2State('expired_state', $config, -1);
        $this->persistAndFlush($expiredState);

        // Create used state
        $usedState = new SinaWeiboOAuth2State('used_state', $config);
        $usedState->markAsUsed();
        $this->persistAndFlush($usedState);

        // Create valid state
        $validState = new SinaWeiboOAuth2State('valid_state', $config);
        $this->persistAndFlush($validState);

        $cleanedCount = $this->stateRepository->cleanupExpiredStates();

        $this->assertEquals(2, $cleanedCount); // expired + used

        // Verify only valid state remains
        $remainingStates = $this->stateRepository->findAll();
        $this->assertCount(1, $remainingStates);
        $this->assertEquals('valid_state', $remainingStates[0]->getState());
    }

    public function testUserRepositoryFindByUid(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app')->setAppSecret('test_secret');
        $this->persistAndFlush($config);

        $user = new SinaWeiboOAuth2User('test_uid', 'test_token', 3600, $config);
        $this->persistAndFlush($user);

        $result = $this->userRepository->findByUid('test_uid');

        $this->assertNotNull($result);
        $this->assertEquals('test_uid', $result->getUid());
    }

    public function testUserRepositoryUpdateOrCreate(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app')->setAppSecret('test_secret');
        $this->persistAndFlush($config);

        // Test create new user
        $userData = [
            'uid' => 'new_uid',
            'access_token' => 'new_token',
            'expires_in' => 3600,
            'screen_name' => 'New User'
        ];

        $user = $this->userRepository->updateOrCreate($userData, $config);
        $this->persistAndFlush($user);

        $this->assertEquals('new_uid', $user->getUid());
        $this->assertEquals('new_token', $user->getAccessToken());
        $this->assertEquals('New User', $user->getNickname());

        // Test update existing user
        $updateData = [
            'uid' => 'new_uid',
            'access_token' => 'updated_token',
            'expires_in' => 7200,
            'screen_name' => 'Updated User'
        ];

        $updatedUser = $this->userRepository->updateOrCreate($updateData, $config);
        $this->persistAndFlush($updatedUser);

        $this->assertEquals($user->getId(), $updatedUser->getId());
        $this->assertEquals('updated_token', $updatedUser->getAccessToken());
        $this->assertEquals('Updated User', $updatedUser->getNickname());
    }

    public function testUserRepositoryFindExpiredTokenUsers(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app')->setAppSecret('test_secret');
        $this->persistAndFlush($config);

        // Create user with expired token and refresh token
        $expiredUser = new SinaWeiboOAuth2User('expired_uid', 'expired_token', -1, $config);
        $expiredUser->setRefreshToken('refresh_token');
        $this->persistAndFlush($expiredUser);

        // Create user with expired token but no refresh token
        $expiredUserNoRefresh = new SinaWeiboOAuth2User('expired_no_refresh', 'expired_token', -1, $config);
        $this->persistAndFlush($expiredUserNoRefresh);

        // Create user with valid token
        $validUser = new SinaWeiboOAuth2User('valid_uid', 'valid_token', 3600, $config);
        $this->persistAndFlush($validUser);

        $results = $this->userRepository->findExpiredTokenUsers();

        $this->assertCount(1, $results); // Only the one with refresh token
        $this->assertEquals('expired_uid', $results[0]->getUid());
    }

    public function testUserRepositoryGetUsersByUids(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app')->setAppSecret('test_secret');
        $this->persistAndFlush($config);

        $user1 = new SinaWeiboOAuth2User('uid1', 'token1', 3600, $config);
        $this->persistAndFlush($user1);

        $user2 = new SinaWeiboOAuth2User('uid2', 'token2', 3600, $config);
        $this->persistAndFlush($user2);

        $user3 = new SinaWeiboOAuth2User('uid3', 'token3', 3600, $config);
        $this->persistAndFlush($user3);

        $results = $this->userRepository->getUsersByUids(['uid1', 'uid3']);

        $this->assertCount(2, $results);
        $uids = array_map(fn($user) => $user->getUid(), $results);
        $this->assertContains('uid1', $uids);
        $this->assertContains('uid3', $uids);
        $this->assertNotContains('uid2', $uids);
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->configRepository = static::getContainer()->get(SinaWeiboOAuth2ConfigRepository::class);
        $this->stateRepository = static::getContainer()->get(SinaWeiboOAuth2StateRepository::class);
        $this->userRepository = static::getContainer()->get(SinaWeiboOAuth2UserRepository::class);
        $this->entityManager = static::getContainer()->get('doctrine')->getManager();

        $this->setupDatabaseSchema();
    }

    private function setupDatabaseSchema(): void
    {
        $em = static::getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($em);

        $classes = [
            $em->getClassMetadata(SinaWeiboOAuth2Config::class),
            $em->getClassMetadata(SinaWeiboOAuth2State::class),
            $em->getClassMetadata(SinaWeiboOAuth2User::class),
        ];

        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
    }
}