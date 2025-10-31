<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Command\SinaWeiboOAuth2CleanupCommand;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2ConfigurationException;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2StateRepository;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2CleanupCommand::class)]
#[RunTestsInSeparateProcesses]
final class SinaWeiboOAuth2CleanupCommandTest extends AbstractCommandTestCase
{
    public function testCleanupWhenNoExpiredStates(): void
    {
        // Create config
        $config = $this->createConfig('test_app', 'test_secret');
        $config->setValid(true);
        $this->persistAndFlush($config);

        // First run to clean up any existing expired states
        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:cleanup');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // Create only valid states (not expired)
        $validState1 = $this->createStateWithData('valid_state1', $config, 600);
        $validState2 = $this->createStateWithData('valid_state2', $config, 3600);

        $this->persistAndFlush($validState1);
        $this->persistAndFlush($validState2);

        // Second run should clean up 0 expired states
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Cleaned up 0 expired states', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testCleanupWithExpiredStates(): void
    {
        // Create config
        $config = $this->createConfig('test_app', 'test_secret');
        $config->setValid(true);
        $this->persistAndFlush($config);

        // First run to clean up any existing expired states
        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:cleanup');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // Create expired states (expire in -1 minutes means already expired)
        $expiredState1 = $this->createStateWithData('expired_state1', $config, -1);
        $expiredState2 = $this->createStateWithData('expired_state2', $config, -10);

        // Create valid state for comparison
        $validState = $this->createStateWithData('valid_state', $config, 600);

        $this->persistAndFlush($expiredState1);
        $this->persistAndFlush($expiredState2);
        $this->persistAndFlush($validState);

        // Second run should clean up the 2 expired states we just created
        $commandTester->execute([]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Cleaned up 2 expired states', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());

        // Verify the expired states are removed and valid state remains
        self::getEntityManager()->clear();
        $stateRepository = self::getService(SinaWeiboOAuth2StateRepository::class);
        $remainingStates = $stateRepository->findAll();

        // Should have at least the valid state
        $this->assertGreaterThanOrEqual(1, count($remainingStates));

        // Find our valid state
        $foundValidState = false;
        foreach ($remainingStates as $state) {
            if ('valid_state' === $state->getState()) {
                $foundValidState = true;
                break;
            }
        }
        $this->assertTrue($foundValidState, 'Valid state should remain after cleanup');
    }

    public function testOptionDryRun(): void
    {
        // Create config
        $config = $this->createConfig('test_app', 'test_secret');
        $config->setValid(true);
        $this->persistAndFlush($config);

        // Create expired state
        $expiredState = $this->createStateWithData('expired_state', $config, -1);
        $this->persistAndFlush($expiredState);

        // Test dry-run option
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--dry-run' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('dry-run', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testCleanupDryRun(): void
    {
        // Create config
        $config = $this->createConfig('test_app', 'test_secret');
        $config->setValid(true);
        $this->persistAndFlush($config);

        // First run to clean up any existing expired states
        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:cleanup');
        $commandTester = new CommandTester($command);
        $commandTester->execute([]);

        // Create expired state
        $expiredState = $this->createStateWithData('expired_state', $config, -1);
        $this->persistAndFlush($expiredState);

        // Test dry-run mode
        $commandTester->execute(['--dry-run' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Running in dry-run mode', $output);
        $this->assertStringContainsString('Would clean up expired states', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());

        // Verify nothing was actually deleted in dry-run mode
        self::getEntityManager()->clear();
        $stateRepository = self::getService(SinaWeiboOAuth2StateRepository::class);
        $remainingStates = $stateRepository->findAll();

        // Should have at least our expired state (since it's dry-run)
        $this->assertGreaterThanOrEqual(1, count($remainingStates));

        // Find our expired state
        $foundExpiredState = false;
        foreach ($remainingStates as $state) {
            if ('expired_state' === $state->getState()) {
                $foundExpiredState = true;
                break;
            }
        }
        $this->assertTrue($foundExpiredState, 'Expired state should remain in dry-run mode');
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(SinaWeiboOAuth2CleanupCommand::class);
        $this->assertInstanceOf(SinaWeiboOAuth2CleanupCommand::class, $command);

        return new CommandTester($command);
    }

    protected function onSetUp(): void
    {
        // 确保数据库架构已创建
        self::cleanDatabase();
    }

    private function createConfig(string $appId, string $appSecret): SinaWeiboOAuth2Config
    {
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId($appId);
        $config->setAppSecret($appSecret);
        $config->setValid(true);

        return $config;
    }

    private function createStateWithData(string $state, SinaWeiboOAuth2Config $config, int $expiresInMinutes): SinaWeiboOAuth2State
    {
        $stateEntity = new SinaWeiboOAuth2State();
        $stateEntity->setState($state);
        $stateEntity->setConfig($config);
        $stateEntity->setExpiresInMinutes($expiresInMinutes);

        return $stateEntity;
    }
}
