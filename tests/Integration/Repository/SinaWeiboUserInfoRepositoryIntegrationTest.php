<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Integration\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboUserInfo;
use Tourze\SinaWeiboOAuth2Bundle\Enum\Gender;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboUserInfoRepository;
use Tourze\SinaWeiboOAuth2Bundle\SinaWeiboOAuth2Bundle;

class SinaWeiboUserInfoRepositoryIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private SinaWeiboUserInfoRepository $repository;

    protected static function createKernel(array $options = []): KernelInterface
    {
        return new IntegrationTestKernel('test', true, [
            SinaWeiboOAuth2Bundle::class => ['all' => true],
        ]);
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->repository = static::getContainer()->get(SinaWeiboUserInfoRepository::class);

        // 创建数据库表结构
        $this->createSchema();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function createSchema(): void
    {
        $metadataFactory = $this->entityManager->getMetadataFactory();
        $metadata = $metadataFactory->getMetadataFor(SinaWeiboUserInfo::class);
        
        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $schemaTool->createSchema([$metadata]);
    }

    public function test_findByWeiboUid_withExistingUser(): void
    {
        // 创建测试用户
        $userInfo = new SinaWeiboUserInfo();
        $userInfo->setWeiboUid('123456789')
            ->setName('Test User')
            ->setScreenName('test_user')
            ->setFollowersCount(100)
            ->setFriendsCount(50)
            ->setStatusesCount(200);

        $this->entityManager->persist($userInfo);
        $this->entityManager->flush();

        // 执行测试
        $foundUser = $this->repository->findByWeiboUid('123456789');

        // 验证结果
        $this->assertNotNull($foundUser);
        $this->assertEquals('123456789', $foundUser->getWeiboUid());
        $this->assertEquals('Test User', $foundUser->getName());
        $this->assertEquals('test_user', $foundUser->getScreenName());
    }

    public function test_findByWeiboUid_withNonExistentUser(): void
    {
        $foundUser = $this->repository->findByWeiboUid('nonexistent_uid');
        $this->assertNull($foundUser);
    }

    public function test_findByScreenName_withExistingUser(): void
    {
        // 创建测试用户
        $userInfo = new SinaWeiboUserInfo();
        $userInfo->setWeiboUid('123456789')
            ->setName('Test User')
            ->setScreenName('unique_screen_name')
            ->setFollowersCount(100);

        $this->entityManager->persist($userInfo);
        $this->entityManager->flush();

        $foundUser = $this->repository->findByScreenName('unique_screen_name');

        $this->assertNotNull($foundUser);
        $this->assertEquals('unique_screen_name', $foundUser->getScreenName());
        $this->assertEquals('123456789', $foundUser->getWeiboUid());
    }

    public function test_findVerifiedUsers(): void
    {
        // 创建多个用户，部分认证、部分未认证
        $verifiedUser1 = new SinaWeiboUserInfo();
        $verifiedUser1->setWeiboUid('verified_uid_1')
            ->setName('Verified User 1')
            ->setScreenName('verified_1')
            ->setVerified(true)
            ->setFollowersCount(1000);

        $verifiedUser2 = new SinaWeiboUserInfo();
        $verifiedUser2->setWeiboUid('verified_uid_2')
            ->setName('Verified User 2')
            ->setScreenName('verified_2')
            ->setVerified(true)
            ->setFollowersCount(2000);

        $normalUser = new SinaWeiboUserInfo();
        $normalUser->setWeiboUid('normal_uid')
            ->setName('Normal User')
            ->setScreenName('normal_user')
            ->setVerified(false)
            ->setFollowersCount(100);

        $this->entityManager->persist($verifiedUser1);
        $this->entityManager->persist($verifiedUser2);
        $this->entityManager->persist($normalUser);
        $this->entityManager->flush();

        // 执行测试
        $verifiedUsers = $this->repository->findVerifiedUsers();

        // 验证结果
        $this->assertCount(2, $verifiedUsers);
        $screenNames = array_map(fn($user) => $user->getScreenName(), $verifiedUsers);
        $this->assertContains('verified_1', $screenNames);
        $this->assertContains('verified_2', $screenNames);
        $this->assertNotContains('normal_user', $screenNames);
    }

    public function test_searchByKeyword(): void
    {
        // 创建测试用户
        $user1 = new SinaWeiboUserInfo();
        $user1->setWeiboUid('search_uid_1')
            ->setName('张三')
            ->setScreenName('zhangsan_123')
            ->setFollowersCount(1000);

        $user2 = new SinaWeiboUserInfo();
        $user2->setWeiboUid('search_uid_2')
            ->setName('李四')
            ->setScreenName('test_lisi')
            ->setFollowersCount(500);

        $user3 = new SinaWeiboUserInfo();
        $user3->setWeiboUid('search_uid_3')
            ->setName('王五')
            ->setScreenName('wangwu_456')
            ->setFollowersCount(2000);

        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->persist($user3);
        $this->entityManager->flush();

        // 按姓名搜索
        $results = $this->repository->searchByKeyword('张三', 10);
        $this->assertCount(1, $results);
        $this->assertEquals('zhangsan_123', $results[0]->getScreenName());

        // 按屏幕名搜索
        $results = $this->repository->searchByKeyword('test', 10);
        $this->assertCount(1, $results);
        $this->assertEquals('test_lisi', $results[0]->getScreenName());

        // 部分匹配搜索
        $results = $this->repository->searchByKeyword('123', 10);
        $this->assertCount(1, $results);
        $this->assertEquals('zhangsan_123', $results[0]->getScreenName());
    }

    public function test_getTopUsersByFollowers(): void
    {
        // 创建不同粉丝数的用户
        $user1 = new SinaWeiboUserInfo();
        $user1->setWeiboUid('top_uid_1')
            ->setName('Top User 1')
            ->setScreenName('top_1')
            ->setFollowersCount(5000);

        $user2 = new SinaWeiboUserInfo();
        $user2->setWeiboUid('top_uid_2')
            ->setName('Top User 2')
            ->setScreenName('top_2')
            ->setFollowersCount(3000);

        $user3 = new SinaWeiboUserInfo();
        $user3->setWeiboUid('top_uid_3')
            ->setName('Top User 3')
            ->setScreenName('top_3')
            ->setFollowersCount(1000);

        $user4 = new SinaWeiboUserInfo();
        $user4->setWeiboUid('top_uid_4')
            ->setName('No Followers User')
            ->setScreenName('no_followers')
            ->setFollowersCount(0);

        $this->entityManager->persist($user1);
        $this->entityManager->persist($user2);
        $this->entityManager->persist($user3);
        $this->entityManager->persist($user4);
        $this->entityManager->flush();

        // 执行测试
        $topUsers = $this->repository->getTopUsersByFollowers(3);

        // 验证结果（按粉丝数降序排列）
        $this->assertCount(3, $topUsers);
        $this->assertEquals('top_1', $topUsers[0]->getScreenName());
        $this->assertEquals(5000, $topUsers[0]->getFollowersCount());
        $this->assertEquals('top_2', $topUsers[1]->getScreenName());
        $this->assertEquals('top_3', $topUsers[2]->getScreenName());
    }

    public function test_findUsersNeedUpdate(): void
    {
        $cutoffDate = new \DateTime('-1 day');

        // 创建需要更新和不需要更新的用户
        $oldUser = new SinaWeiboUserInfo();
        $oldUser->setWeiboUid('old_uid')
            ->setName('Old User')
            ->setScreenName('old_user')
            ->setUpdateTime(new \DateTime('-2 days'))
            ->setFollowersCount(100);

        $recentUser = new SinaWeiboUserInfo();
        $recentUser->setWeiboUid('recent_uid')
            ->setName('Recent User')
            ->setScreenName('recent_user')
            ->setUpdateTime(new \DateTime('-1 hour'))
            ->setFollowersCount(200);

        $veryOldUser = new SinaWeiboUserInfo();
        $veryOldUser->setWeiboUid('very_old_uid')
            ->setName('Very Old User')
            ->setScreenName('very_old_user')
            ->setUpdateTime(new \DateTime('-10 days'))
            ->setFollowersCount(300);

        $this->entityManager->persist($oldUser);
        $this->entityManager->persist($recentUser);
        $this->entityManager->persist($veryOldUser);
        $this->entityManager->flush();

        // 执行测试
        $usersNeedUpdate = $this->repository->findUsersNeedUpdate($cutoffDate, 10);

        // 验证结果（按更新时间升序排列）
        $this->assertCount(2, $usersNeedUpdate);
        $this->assertEquals('very_old_user', $usersNeedUpdate[0]->getScreenName());
        $this->assertEquals('old_user', $usersNeedUpdate[1]->getScreenName());
    }

    public function test_saveOrUpdate_newUser(): void
    {
        // 创建新用户
        $newUser = new SinaWeiboUserInfo();
        $newUser->setWeiboUid('new_uid')
            ->setName('New User')
            ->setScreenName('new_user')
            ->setGender(Gender::MALE)
            ->setLocation('北京')
            ->setDescription('新用户描述')
            ->setFollowersCount(50)
            ->setFriendsCount(30)
            ->setStatusesCount(10);

        // 执行保存
        $savedUser = $this->repository->saveOrUpdate($newUser);
        $this->entityManager->flush();

        // 验证结果
        $this->assertSame($newUser, $savedUser);
        $this->assertNotNull($savedUser->getId());

        // 从数据库验证
        $this->entityManager->clear();
        $foundUser = $this->repository->findByWeiboUid('new_uid');
        $this->assertNotNull($foundUser);
        $this->assertEquals('New User', $foundUser->getName());
        $this->assertEquals(Gender::MALE, $foundUser->getGender());
    }

    public function test_saveOrUpdate_existingUser(): void
    {
        // 创建并保存原始用户
        $originalUser = new SinaWeiboUserInfo();
        $originalUser->setWeiboUid('existing_uid')
            ->setName('Original Name')
            ->setScreenName('original_screen')
            ->setFollowersCount(100)
            ->setFriendsCount(50);

        $this->entityManager->persist($originalUser);
        $this->entityManager->flush();
        $originalId = $originalUser->getId();

        // 创建更新用户数据
        $updatedUser = new SinaWeiboUserInfo();
        $updatedUser->setWeiboUid('existing_uid')
            ->setName('Updated Name')
            ->setScreenName('updated_screen')
            ->setGender(Gender::FEMALE)
            ->setLocation('上海')
            ->setFollowersCount(200)
            ->setFriendsCount(75)
            ->setStatusesCount(150);

        // 执行更新
        $savedUser = $this->repository->saveOrUpdate($updatedUser);
        $this->entityManager->flush();

        // 验证结果（应该返回原始实体对象，但数据已更新）
        $this->assertNotSame($updatedUser, $savedUser);
        $this->assertEquals($originalId, $savedUser->getId());
        $this->assertEquals('Updated Name', $savedUser->getName());
        $this->assertEquals('updated_screen', $savedUser->getScreenName());
        $this->assertEquals(Gender::FEMALE, $savedUser->getGender());
        $this->assertEquals('上海', $savedUser->getLocation());
        $this->assertEquals(200, $savedUser->getFollowersCount());

        // 验证数据库中只有一条记录
        $allUsers = $this->repository->findBy(['weiboUid' => 'existing_uid']);
        $this->assertCount(1, $allUsers);
    }

    public function test_fillFromApiResponse(): void
    {
        $apiData = [
            'id' => 123456789,
            'name' => 'API User',
            'screen_name' => 'api_user',
            'location' => '深圳',
            'description' => 'API用户描述',
            'url' => 'http://example.com',
            'profile_image_url' => 'http://example.com/avatar.jpg',
            'gender' => 'f',
            'followers_count' => 1500,
            'friends_count' => 300,
            'statuses_count' => 2000,
            'verified' => true,
            'verified_type' => 1,
            'user_level' => 5,
            'created_at' => 'Mon Aug 08 21:46:22 +0800 2011'
        ];

        $userInfo = new SinaWeiboUserInfo();
        $userInfo->fillFromApiResponse($apiData);

        // 验证填充结果
        $this->assertEquals('123456789', $userInfo->getWeiboUid());
        $this->assertEquals('API User', $userInfo->getName());
        $this->assertEquals('api_user', $userInfo->getScreenName());
        $this->assertEquals('深圳', $userInfo->getLocation());
        $this->assertEquals('API用户描述', $userInfo->getDescription());
        $this->assertEquals('http://example.com', $userInfo->getUrl());
        $this->assertEquals('http://example.com/avatar.jpg', $userInfo->getProfileImageUrl());
        $this->assertEquals(Gender::FEMALE, $userInfo->getGender());
        $this->assertEquals(1500, $userInfo->getFollowersCount());
        $this->assertEquals(300, $userInfo->getFriendsCount());
        $this->assertEquals(2000, $userInfo->getStatusesCount());
        $this->assertTrue($userInfo->isVerified());
        $this->assertEquals(1, $userInfo->getVerifiedType());
        $this->assertEquals(5, $userInfo->getUserLevel());
        $this->assertEquals($apiData, $userInfo->getRawData());
        $this->assertInstanceOf(\DateTime::class, $userInfo->getWeiboCreateTime());
    }
} 