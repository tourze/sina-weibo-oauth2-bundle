<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Command;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2ConfigRepository;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2StateRepository;
use Tourze\SinaWeiboOAuth2Bundle\Tests\TestKernel;

class SinaWeiboOAuth2CommandTest extends KernelTestCase
{
    private Application $application;
    private SinaWeiboOAuth2ConfigRepository $configRepository;
    private SinaWeiboOAuth2StateRepository $stateRepository;
    private $entityManager;

    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    public function testConfigListCommand(): void
    {
        $command = $this->application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        // Test with no configs
        $commandTester->execute(['action' => 'list']);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('No Sina Weibo OAuth2 configurations found', $output);

        // Create a config
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app_id')
            ->setAppSecret('test_secret')
            ->setScope('email');
        $this->persistAndFlush($config);

        // Test with configs
        $commandTester->execute(['action' => 'list']);
        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('test_app_id', $output);
        $this->assertStringContainsString('email', $output);
    }

    private function persistAndFlush($entity): void
    {
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function testConfigCreateCommand(): void
    {
        $command = $this->application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        // Clear any existing cache
        $this->configRepository->invalidateCache();

        $commandTester->execute([
            'action' => 'create',
            '--app-id' => 'new_app_id',
            '--app-secret' => 'new_app_secret',
            '--scope' => 'profile,email',
            '--active' => 'true'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Created Sina Weibo OAuth2 configuration', $output);

        // Verify config was created
        $config = $this->configRepository->findValidConfig();
        $this->assertNotNull($config);
        $this->assertEquals('new_app_id', $config->getAppId());
        $this->assertEquals('new_app_secret', $config->getAppSecret());
        $this->assertEquals('profile,email', $config->getScope());
        $this->assertTrue($config->isActive());
    }

    public function testConfigCreateCommandWithMissingParameters(): void
    {
        $command = $this->application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['action' => 'create']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('App ID and App Secret are required', $output);
    }

    public function testConfigUpdateCommand(): void
    {
        // Create initial config
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('old_app_id')
            ->setAppSecret('old_secret')
            ->setScope('email');
        $this->persistAndFlush($config);

        $command = $this->application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'action' => 'update',
            '--id' => $config->getId(),
            '--app-id' => 'updated_app_id',
            '--scope' => 'profile'
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Updated Sina Weibo OAuth2 configuration', $output);

        // Verify config was updated
        $updatedConfig = $this->configRepository->find($config->getId());
        $this->assertEquals('updated_app_id', $updatedConfig->getAppId());
        $this->assertEquals('old_secret', $updatedConfig->getAppSecret()); // Should remain unchanged
        $this->assertEquals('profile', $updatedConfig->getScope());
    }

    public function testConfigDeleteCommand(): void
    {
        // Create config to delete
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('delete_me')
            ->setAppSecret('delete_secret');
        $this->persistAndFlush($config);

        $command = $this->application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        // Refresh entity to ensure ID is available
        $this->entityManager->refresh($config);
        $configId = $config->getId();
        $this->assertNotNull($configId, 'Config ID should not be null after flush');
        
        $commandTester->execute([
            'action' => 'delete',
            '--id' => $configId
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Deleted Sina Weibo OAuth2 configuration', $output);

        // Verify config was deleted
        $deletedConfig = $this->configRepository->find($configId);
        $this->assertNull($deletedConfig);
    }

    public function testCleanupCommand(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app')->setAppSecret('test_secret');
        $this->persistAndFlush($config);

        // Create expired state
        $expiredState = new SinaWeiboOAuth2State('expired_state', $config, -1);
        $this->persistAndFlush($expiredState);

        // Create valid state
        $validState = new SinaWeiboOAuth2State('valid_state', $config);
        $this->persistAndFlush($validState);

        $command = $this->application->find('sina-weibo-oauth2:cleanup');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Cleaned up 1 expired states', $output);

        // Verify cleanup worked
        $remainingStates = $this->stateRepository->findAll();
        $this->assertCount(1, $remainingStates);
        $this->assertEquals('valid_state', $remainingStates[0]->getState());
    }

    public function testCleanupCommandDryRun(): void
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('test_app')->setAppSecret('test_secret');
        $this->persistAndFlush($config);

        // Create expired state
        $expiredState = new SinaWeiboOAuth2State('expired_state', $config, -1);
        $this->persistAndFlush($expiredState);

        $command = $this->application->find('sina-weibo-oauth2:cleanup');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['--dry-run' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Running in dry-run mode', $output);
        $this->assertStringContainsString('Would clean up expired states', $output);

        // Verify nothing was actually cleaned up
        $remainingStates = $this->stateRepository->findAll();
        $this->assertCount(1, $remainingStates);
    }

    public function testRefreshTokenCommand(): void
    {
        $command = $this->application->find('sina-weibo-oauth2:refresh-tokens');
        $commandTester = new CommandTester($command);

        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Sina Weibo API does not support refresh tokens', $output);
        $this->assertStringContainsString('No tokens were refreshed', $output);
    }

    public function testRefreshTokenCommandDryRun(): void
    {
        $command = $this->application->find('sina-weibo-oauth2:refresh-tokens');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['--dry-run' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Running in dry-run mode', $output);
        $this->assertStringContainsString('Would attempt to refresh expired tokens', $output);
    }

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->application = new Application($kernel);
        $this->configRepository = static::getContainer()->get(SinaWeiboOAuth2ConfigRepository::class);
        $this->stateRepository = static::getContainer()->get(SinaWeiboOAuth2StateRepository::class);
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