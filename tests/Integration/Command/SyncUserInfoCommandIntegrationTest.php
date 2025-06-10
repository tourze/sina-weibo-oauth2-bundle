<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Integration\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\HttpKernel\KernelInterface;
use Tourze\IntegrationTestKernel\IntegrationTestKernel;
use Tourze\SinaWeiboOAuth2Bundle\Command\SyncUserInfoCommand;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboAppConfig;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Token;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboUserInfo;
use Tourze\SinaWeiboOAuth2Bundle\SinaWeiboOAuth2Bundle;

class SyncUserInfoCommandIntegrationTest extends KernelTestCase
{
    private EntityManagerInterface $entityManager;
    private Application $application;
    private SinaWeiboAppConfig $testAppConfig;

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

        // 创建数据库表结构
        $this->createSchema();

        // 创建测试用的应用配置
        $this->createTestAppConfig();

        // 设置Console应用
        $this->application = new Application(static::$kernel);
        $this->application->add(static::getContainer()->get(SyncUserInfoCommand::class));
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->entityManager->close();
    }

    private function createSchema(): void
    {
        $metadataFactory = $this->entityManager->getMetadataFactory();
        $metadata = [
            $metadataFactory->getMetadataFor(SinaWeiboAppConfig::class),
            $metadataFactory->getMetadataFor(SinaWeiboOAuth2Token::class),
            $metadataFactory->getMetadataFor(SinaWeiboUserInfo::class),
        ];

        $schemaTool = new \Doctrine\ORM\Tools\SchemaTool($this->entityManager);
        $schemaTool->createSchema($metadata);
    }

    private function createTestAppConfig(): void
    {
        $this->testAppConfig = new SinaWeiboAppConfig();
        $this->testAppConfig->setAppName('Test App')
            ->setAppKey('test_app_key')
            ->setAppSecret('test_secret')
            ->setRedirectUri('http://example.com/callback')
            ->setValid(true);

        $this->entityManager->persist($this->testAppConfig);
        $this->entityManager->flush();
    }

    public function test_command_with_no_users_to_sync(): void
    {
        $command = $this->application->find('weibo:sync-user-info');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('没有需要同步的用户', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function test_command_with_specific_weibo_uid(): void
    {
        // 创建测试用户信息
        $userInfo = new SinaWeiboUserInfo();
        $userInfo->setWeiboUid('123456789')
            ->setName('Test User')
            ->setScreenName('test_user')
            ->setFollowersCount(100)
            ->setFriendsCount(50)
            ->setStatusesCount(200);

        $this->entityManager->persist($userInfo);
        $this->entityManager->flush();

        $command = $this->application->find('weibo:sync-user-info');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'weibo-uid' => '123456789',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('找到 1 个用户需要同步', $output);
        $this->assertStringContainsString('没有有效的访问令牌', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }

    public function test_command_with_dry_run_option(): void
    {
        // 创建测试用户
        $userInfo = new SinaWeiboUserInfo();
        $userInfo->setWeiboUid('dry_run_user')
            ->setName('Dry Run User')
            ->setScreenName('dry_run_user')
            ->setUpdateTime(new \DateTime('-10 days'))
            ->setFollowersCount(100);

        $this->entityManager->persist($userInfo);
        $this->entityManager->flush();

        $command = $this->application->find('weibo:sync-user-info');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            '--dry-run' => true,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('运行在试运行模式', $output);
        $this->assertStringContainsString('将同步用户：dry_run_user', $output);
        $this->assertEquals(Command::SUCCESS, $commandTester->getStatusCode());
    }
} 