<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2UserRepository;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2UserRepository::class)]
#[RunTestsInSeparateProcesses]
final class SinaWeiboOAuth2UserRepositoryTest extends AbstractRepositoryTestCase
{
    private SinaWeiboOAuth2UserRepository $repository;

    private SinaWeiboOAuth2Config $config;

    public function testFindByUidReturnsNullWhenUserNotFound(): void
    {
        $result = $this->repository->findByUid('non_existent_uid');

        $this->assertNull($result);
    }

    public function testFindByUidReturnsLatestUserWhenMultipleExist(): void
    {
        $now = new \DateTimeImmutable();

        $config2 = new SinaWeiboOAuth2Config();
        $config2->setAppId('test_app_id_2');
        $config2->setAppSecret('test_secret_2');
        $config2->setValid(true);
        $config2->setCreateTime(new \DateTimeImmutable());
        $this->persistAndFlush($config2);

        $user1 = new SinaWeiboOAuth2User();
        $user1->setUid('test_uid');
        $user1->setAccessToken('token1');
        $user1->setExpiresIn(3600);
        $user1->setConfig($this->config);
        $user1->setNickname('First User');
        $user1->setCreateTime($now->modify('-1 minute'));

        $user2 = new SinaWeiboOAuth2User();
        $user2->setUid('test_uid');
        $user2->setAccessToken('token2');
        $user2->setExpiresIn(3600);
        $user2->setConfig($config2);
        $user2->setNickname('Second User');
        $user2->setCreateTime($now);

        $this->persistAndFlush($user1);
        $this->persistAndFlush($user2);

        $result = $this->repository->findByUid('test_uid');

        $this->assertNotNull($result);
        $this->assertSame('test_uid', $result->getUid());
        $this->assertSame('Second User', $result->getNickname());
        $this->assertSame('token2', $result->getAccessToken());
    }

    public function testFindByUidAndConfigForUpdateReturnsCorrectUser(): void
    {
        $anotherConfig = new SinaWeiboOAuth2Config();
        $anotherConfig->setAppId('another_app');
        $anotherConfig->setAppSecret('another_secret');
        $anotherConfig->setValid(true);
        $anotherConfig->setCreateTime(new \DateTimeImmutable());
        $this->persistAndFlush($anotherConfig);

        $user1 = new SinaWeiboOAuth2User();
        $user1->setUid('test_uid');
        $user1->setAccessToken('token1');
        $user1->setExpiresIn(3600);
        $user1->setConfig($this->config);

        $user2 = new SinaWeiboOAuth2User();
        $user2->setUid('test_uid');
        $user2->setAccessToken('token2');
        $user2->setExpiresIn(3600);
        $user2->setConfig($anotherConfig);

        $this->persistAndFlush($user1);
        $this->persistAndFlush($user2);

        $result = $this->repository->findByUidAndConfigForUpdate('test_uid', $this->config);

        $this->assertNotNull($result);
        $this->assertSame('test_uid', $result->getUid());
        $this->assertSame($this->config->getId(), $result->getConfig()?->getId());
        $this->assertSame('token1', $result->getAccessToken());
    }

    public function testFindByUidAndConfigForUpdateReturnsNullWhenNotFound(): void
    {
        $result = $this->repository->findByUidAndConfigForUpdate('non_existent', $this->config);

        $this->assertNull($result);
    }

    public function testFindByUidAndConfigReturnsCorrectUser(): void
    {
        $anotherConfig = new SinaWeiboOAuth2Config();
        $anotherConfig->setAppId('another_app');
        $anotherConfig->setAppSecret('another_secret');
        $anotherConfig->setValid(true);
        $anotherConfig->setCreateTime(new \DateTimeImmutable());
        $this->persistAndFlush($anotherConfig);

        $user1 = new SinaWeiboOAuth2User();
        $user1->setUid('test_uid');
        $user1->setAccessToken('token1');
        $user1->setExpiresIn(3600);
        $user1->setConfig($this->config);

        $user2 = new SinaWeiboOAuth2User();
        $user2->setUid('test_uid');
        $user2->setAccessToken('token2');
        $user2->setExpiresIn(3600);
        $user2->setConfig($anotherConfig);

        $this->persistAndFlush($user1);
        $this->persistAndFlush($user2);

        $result = $this->repository->findByUidAndConfig('test_uid', $this->config);

        $this->assertNotNull($result);
        $this->assertSame('test_uid', $result->getUid());
        $this->assertSame($this->config->getId(), $result->getConfig()?->getId());
        $this->assertSame('token1', $result->getAccessToken());
    }

    public function testFindExpiredTokenUsersReturnsOnlyExpiredUsersWithRefreshToken(): void
    {
        $em = self::getEntityManager();
        $existingUsers = $this->repository->findAll();
        foreach ($existingUsers as $user) {
            $em->remove($user);
        }
        $em->flush();

        $validUser = new SinaWeiboOAuth2User();
        $validUser->setUid('valid_uid');
        $validUser->setAccessToken('valid_token');
        $validUser->setExpiresIn(3600);
        $validUser->setConfig($this->config);
        $validUser->setRefreshToken('valid_refresh');

        $expiredUser = new SinaWeiboOAuth2User();
        $expiredUser->setUid('expired_uid');
        $expiredUser->setAccessToken('expired_token');
        $expiredUser->setExpiresIn(-1);
        $expiredUser->setConfig($this->config);
        $expiredUser->setRefreshToken('expired_refresh');

        $expiredUserNoRefresh = new SinaWeiboOAuth2User();
        $expiredUserNoRefresh->setUid('expired_no_refresh');
        $expiredUserNoRefresh->setAccessToken('token');
        $expiredUserNoRefresh->setExpiresIn(-1);
        $expiredUserNoRefresh->setConfig($this->config);

        $this->persistAndFlush($validUser);
        $this->persistAndFlush($expiredUser);
        $this->persistAndFlush($expiredUserNoRefresh);

        $results = $this->repository->findExpiredTokenUsers();

        $this->assertCount(1, $results);
        $this->assertSame('expired_uid', $results[0]->getUid());
        $this->assertNotNull($results[0]->getRefreshToken());
        $this->assertTrue($results[0]->isTokenExpired());
    }

    public function testFindUsersWithValidTokensReturnsOnlyValidUsers(): void
    {
        $em = self::getEntityManager();
        $existingUsers = $this->repository->findAll();
        foreach ($existingUsers as $user) {
            $em->remove($user);
        }
        $em->flush();

        $now = new \DateTimeImmutable();

        $validUser1 = new SinaWeiboOAuth2User();
        $validUser1->setUid('valid_uid_1');
        $validUser1->setAccessToken('valid_token_1');
        $validUser1->setExpiresIn(3600);
        $validUser1->setConfig($this->config);
        $validUser1->setCreateTime($now->modify('-1 minute'));

        $validUser2 = new SinaWeiboOAuth2User();
        $validUser2->setUid('valid_uid_2');
        $validUser2->setAccessToken('valid_token_2');
        $validUser2->setExpiresIn(7200);
        $validUser2->setConfig($this->config);
        $validUser2->setCreateTime($now);

        $expiredUser = new SinaWeiboOAuth2User();
        $expiredUser->setUid('expired_uid');
        $expiredUser->setAccessToken('expired_token');
        $expiredUser->setExpiresIn(-1);
        $expiredUser->setConfig($this->config);
        $expiredUser->setCreateTime($now);

        $this->persistAndFlush($validUser1);
        $this->persistAndFlush($validUser2);
        $this->persistAndFlush($expiredUser);

        $results = $this->repository->findUsersWithValidTokens();

        $this->assertCount(2, $results);
        $uids = array_map(fn ($user) => $user->getUid(), $results);
        $this->assertContains('valid_uid_1', $uids);
        $this->assertContains('valid_uid_2', $uids);
        $this->assertNotContains('expired_uid', $uids);

        $this->assertSame('valid_uid_2', $results[0]->getUid());
        $this->assertSame('valid_uid_1', $results[1]->getUid());
    }

    public function testGetUsersByConfigReturnsUsersForSpecificConfig(): void
    {
        $now = new \DateTimeImmutable();

        $anotherConfig = new SinaWeiboOAuth2Config();
        $anotherConfig->setAppId('another_app');
        $anotherConfig->setAppSecret('another_secret');
        $anotherConfig->setValid(true);
        $anotherConfig->setCreateTime(new \DateTimeImmutable());
        $this->persistAndFlush($anotherConfig);

        $user1 = new SinaWeiboOAuth2User();
        $user1->setUid('uid_1');
        $user1->setAccessToken('token_1');
        $user1->setExpiresIn(3600);
        $user1->setConfig($this->config);
        $user1->setCreateTime($now->modify('-1 minute'));

        $user2 = new SinaWeiboOAuth2User();
        $user2->setUid('uid_2');
        $user2->setAccessToken('token_2');
        $user2->setExpiresIn(3600);
        $user2->setConfig($this->config);
        $user2->setCreateTime($now);

        $user3 = new SinaWeiboOAuth2User();
        $user3->setUid('uid_3');
        $user3->setAccessToken('token_3');
        $user3->setExpiresIn(3600);
        $user3->setConfig($anotherConfig);
        $user3->setCreateTime($now);

        $this->persistAndFlush($user1);
        $this->persistAndFlush($user2);
        $this->persistAndFlush($user3);

        $results = $this->repository->getUsersByConfig($this->config);

        $this->assertCount(2, $results);
        $uids = array_map(fn ($user) => $user->getUid(), $results);
        $this->assertContains('uid_1', $uids);
        $this->assertContains('uid_2', $uids);
        $this->assertNotContains('uid_3', $uids);

        $this->assertSame('uid_2', $results[0]->getUid());
        $this->assertSame('uid_1', $results[1]->getUid());
    }

    public function testGetUsersByUidsReturnsUsersWithMatchingUids(): void
    {
        $user1 = new SinaWeiboOAuth2User();
        $user1->setUid('uid_1');
        $user1->setAccessToken('token_1');
        $user1->setExpiresIn(3600);
        $user1->setConfig($this->config);

        $user2 = new SinaWeiboOAuth2User();
        $user2->setUid('uid_2');
        $user2->setAccessToken('token_2');
        $user2->setExpiresIn(3600);
        $user2->setConfig($this->config);

        $user3 = new SinaWeiboOAuth2User();
        $user3->setUid('uid_3');
        $user3->setAccessToken('token_3');
        $user3->setExpiresIn(3600);
        $user3->setConfig($this->config);

        $this->persistAndFlush($user1);
        $this->persistAndFlush($user2);
        $this->persistAndFlush($user3);

        $results = $this->repository->getUsersByUids(['uid_1', 'uid_3', 'non_existent']);

        $this->assertCount(2, $results);
        $uids = array_map(fn ($user) => $user->getUid(), $results);
        $this->assertContains('uid_1', $uids);
        $this->assertContains('uid_3', $uids);
        $this->assertNotContains('uid_2', $uids);
    }

    public function testGetUsersByUidsReturnsEmptyArrayForEmptyInput(): void
    {
        $results = $this->repository->getUsersByUids([]);

        $this->assertIsArray($results);
        $this->assertEmpty($results);
    }

    public function testUpdateOrCreateCreatesNewUserWhenNotExists(): void
    {
        $userData = [
            'accessToken' => 'new_token',
            'expiresIn' => 7200,
            'refreshToken' => 'new_refresh_token',
            'nickname' => 'New User',
            'avatar' => 'https://example.com/avatar.jpg',
            'gender' => 'male',
            'location' => 'Beijing',
            'description' => 'Test user description',
            'rawData' => ['id' => 123456789, 'name' => 'Test'],
        ];

        $result = $this->repository->updateOrCreate('new_uid', $this->config, $userData);

        $this->assertNotNull($result);
        $this->assertSame('new_uid', $result->getUid());
        $this->assertSame('new_token', $result->getAccessToken());
        $this->assertSame('new_refresh_token', $result->getRefreshToken());
        $this->assertSame('New User', $result->getNickname());
        $this->assertSame('https://example.com/avatar.jpg', $result->getAvatar());
        $this->assertSame('male', $result->getGender());
        $this->assertSame('Beijing', $result->getLocation());
        $this->assertSame('Test user description', $result->getDescription());
        $this->assertSame(['id' => 123456789, 'name' => 'Test'], $result->getRawData());

        $this->assertEntityPersisted($result);
    }

    public function testUpdateOrCreateUpdatesExistingUser(): void
    {
        $existingUser = new SinaWeiboOAuth2User();
        $existingUser->setUid('existing_uid');
        $existingUser->setAccessToken('old_token');
        $existingUser->setExpiresIn(3600);
        $existingUser->setConfig($this->config);
        $existingUser->setNickname('Old Name');
        $this->persistAndFlush($existingUser);

        $userData = [
            'accessToken' => 'updated_token',
            'expiresIn' => 7200,
            'nickname' => 'Updated Name',
            'avatar' => 'https://example.com/new_avatar.jpg',
        ];

        $result = $this->repository->updateOrCreate('existing_uid', $this->config, $userData);

        $this->assertNotNull($result);
        $this->assertSame($existingUser->getId(), $result->getId());
        $this->assertSame('existing_uid', $result->getUid());
        $this->assertSame('updated_token', $result->getAccessToken());
        $this->assertSame('Updated Name', $result->getNickname());
        $this->assertSame('https://example.com/new_avatar.jpg', $result->getAvatar());

        $found = self::getEntityManager()->find(SinaWeiboOAuth2User::class, $result->getId());
        $this->assertNotNull($found);
        $this->assertSame('Updated Name', $found->getNickname());
    }

    public function testUpdateOrCreateHandlesInvalidSetters(): void
    {
        $userData = [
            'accessToken' => 'test_token',
            'expiresIn' => 3600,
            'invalidProperty' => 'should_be_ignored',
            'nickname' => 'Valid Name',
        ];

        $result = $this->repository->updateOrCreate('test_uid', $this->config, $userData);

        $this->assertNotNull($result);
        $this->assertSame('test_uid', $result->getUid());
        $this->assertSame('test_token', $result->getAccessToken());
        $this->assertSame('Valid Name', $result->getNickname());
    }

    public function testSaveEntityPersistsUser(): void
    {
        $user = new SinaWeiboOAuth2User();
        $user->setUid('save_uid');
        $user->setAccessToken('save_token');
        $user->setExpiresIn(3600);
        $user->setConfig($this->config);
        $user->setNickname('Save Test User');

        $this->repository->save($user);

        $this->assertEntityPersisted($user);

        $found = self::getEntityManager()->find(SinaWeiboOAuth2User::class, $user->getId());
        $this->assertNotNull($found);
        $this->assertSame('save_uid', $found->getUid());
        $this->assertSame('Save Test User', $found->getNickname());
    }

    public function testSaveEntityWithoutFlushDoesNotPersistImmediately(): void
    {
        $user = new SinaWeiboOAuth2User();
        $user->setUid('no_flush_uid');
        $user->setAccessToken('no_flush_token');
        $user->setExpiresIn(3600);
        $user->setConfig($this->config);

        $this->repository->save($user, false);

        $em = self::getEntityManager();

        $allUsers = $this->repository->findBy(['uid' => 'no_flush_uid']);
        $this->assertEmpty($allUsers);

        $em->flush();

        $allUsers = $this->repository->findBy(['uid' => 'no_flush_uid']);
        $this->assertCount(1, $allUsers);
        $this->assertSame('no_flush_uid', $allUsers[0]->getUid());
    }

    public function testRemoveEntityDeletesUser(): void
    {
        $user = new SinaWeiboOAuth2User();
        $user->setUid('delete_uid');
        $user->setAccessToken('delete_token');
        $user->setExpiresIn(3600);
        $user->setConfig($this->config);

        $this->persistAndFlush($user);
        $userId = $user->getId();

        $this->repository->remove($user);

        $this->assertEntityNotExists(SinaWeiboOAuth2User::class, $userId);

        $found = self::getEntityManager()->find(SinaWeiboOAuth2User::class, $userId);
        $this->assertNull($found);
    }

    public function testRemoveEntityWithoutFlushDoesNotDeleteImmediately(): void
    {
        $user = new SinaWeiboOAuth2User();
        $user->setUid('delete_no_flush_uid');
        $user->setAccessToken('delete_token');
        $user->setExpiresIn(3600);
        $user->setConfig($this->config);

        $this->persistAndFlush($user);
        $userId = $user->getId();

        $this->repository->remove($user, false);

        $found = self::getEntityManager()->find(SinaWeiboOAuth2User::class, $userId);
        $this->assertNotNull($found);

        self::getEntityManager()->flush();
        $this->assertEntityNotExists(SinaWeiboOAuth2User::class, $userId);
    }

    public function testUpdateOrCreateWithMinimalData(): void
    {
        $userData = [
            'accessToken' => 'minimal_token',
            'expiresIn' => 3600,
        ];

        $result = $this->repository->updateOrCreate('minimal_uid', $this->config, $userData);

        $this->assertNotNull($result);
        $this->assertSame('minimal_uid', $result->getUid());
        $this->assertSame('minimal_token', $result->getAccessToken());
        $this->assertSame($this->config->getId(), $result->getConfig()?->getId());
    }

    public function testFindOneByAssociationConfigShouldReturnMatchingEntity(): void
    {
        $anotherConfig = new SinaWeiboOAuth2Config();
        $anotherConfig->setAppId('another_app');
        $anotherConfig->setAppSecret('another_secret');
        $anotherConfig->setValid(true);
        $this->persistAndFlush($anotherConfig);

        $user1 = new SinaWeiboOAuth2User();
        $user1->setUid('uid_1');
        $user1->setAccessToken('token_1');
        $user1->setExpiresIn(3600);
        $user1->setConfig($this->config);

        $user2 = new SinaWeiboOAuth2User();
        $user2->setUid('uid_2');
        $user2->setAccessToken('token_2');
        $user2->setExpiresIn(3600);
        $user2->setConfig($anotherConfig);

        $this->persistAndFlush($user1);
        $this->persistAndFlush($user2);

        $result = $this->repository->findOneBy(['config' => $this->config]);

        $this->assertNotNull($result);
        $this->assertSame('uid_1', $result->getUid());
        $this->assertSame($this->config->getId(), $result->getConfig()?->getId());
    }

    public function testCountByAssociationConfigShouldReturnCorrectNumber(): void
    {
        $anotherConfig = new SinaWeiboOAuth2Config();
        $anotherConfig->setAppId('another_app');
        $anotherConfig->setAppSecret('another_secret');
        $anotherConfig->setValid(true);
        $this->persistAndFlush($anotherConfig);

        $user1 = new SinaWeiboOAuth2User();
        $user1->setUid('uid_1');
        $user1->setAccessToken('token_1');
        $user1->setExpiresIn(3600);
        $user1->setConfig($this->config);

        $user2 = new SinaWeiboOAuth2User();
        $user2->setUid('uid_2');
        $user2->setAccessToken('token_2');
        $user2->setExpiresIn(3600);
        $user2->setConfig($this->config);

        $user3 = new SinaWeiboOAuth2User();
        $user3->setUid('uid_3');
        $user3->setAccessToken('token_3');
        $user3->setExpiresIn(3600);
        $user3->setConfig($this->config);

        $user4 = new SinaWeiboOAuth2User();
        $user4->setUid('uid_4');
        $user4->setAccessToken('token_4');
        $user4->setExpiresIn(3600);
        $user4->setConfig($this->config);

        $user5 = new SinaWeiboOAuth2User();
        $user5->setUid('uid_5');
        $user5->setAccessToken('token_5');
        $user5->setExpiresIn(3600);
        $user5->setConfig($anotherConfig);

        $user6 = new SinaWeiboOAuth2User();
        $user6->setUid('uid_6');
        $user6->setAccessToken('token_6');
        $user6->setExpiresIn(3600);
        $user6->setConfig($anotherConfig);

        $this->persistAndFlush($user1);
        $this->persistAndFlush($user2);
        $this->persistAndFlush($user3);
        $this->persistAndFlush($user4);
        $this->persistAndFlush($user5);
        $this->persistAndFlush($user6);

        $count = $this->repository->count(['config' => $this->config]);

        $this->assertSame(4, $count);
    }

    protected function onSetUp(): void
    {
        $this->repository = self::getService(SinaWeiboOAuth2UserRepository::class);

        $this->config = new SinaWeiboOAuth2Config();
        $this->config->setAppId('test_app_id');
        $this->config->setAppSecret('test_secret');
        $this->config->setScope(null);
        $this->config->setValid(true);
        $this->config->setCreateTime(new \DateTimeImmutable());

        $this->persistAndFlush($this->config);
    }

    protected function createNewEntity(): object
    {
        $entity = new SinaWeiboOAuth2User();
        $entity->setUid('test_uid_' . uniqid());
        $entity->setAccessToken('test_token_' . uniqid());
        $entity->setExpiresIn(3600);
        $entity->setConfig($this->config);
        $entity->setNickname('Test User ' . uniqid());

        return $entity;
    }

    /**
     * @return ServiceEntityRepository<SinaWeiboOAuth2User>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }
}
