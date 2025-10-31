<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Command\SinaWeiboOAuth2RefreshTokenCommand;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2RefreshTokenCommand::class)]
#[RunTestsInSeparateProcesses]
final class SinaWeiboOAuth2RefreshTokenCommandTest extends AbstractCommandTestCase
{
    protected function onSetUp(): void
    {
        // 确保数据库架构已创建
        self::cleanDatabase();
    }

    public function testOptionDryRun(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute(['--dry-run' => true]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('dry-run', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testCommandHasCorrectName(): void
    {
        $command = self::getService(SinaWeiboOAuth2RefreshTokenCommand::class);
        $commandTester = new CommandTester($command);

        $this->assertEquals('sina-weibo-oauth2:refresh-tokens', $command->getName());
    }

    public function testCommandHasCorrectDescription(): void
    {
        $command = self::getService(SinaWeiboOAuth2RefreshTokenCommand::class);
        $commandTester = new CommandTester($command);

        $this->assertStringContainsString('Refresh expired OAuth2 access tokens', $command->getDescription());
    }

    public function testCommandHasDryRunOption(): void
    {
        $command = self::getService(SinaWeiboOAuth2RefreshTokenCommand::class);
        $commandTester = new CommandTester($command);

        $definition = $command->getDefinition();
        $this->assertTrue($definition->hasOption('dry-run'));

        $dryRunOption = $definition->getOption('dry-run');
        $this->assertFalse($dryRunOption->acceptValue());
        $this->assertFalse($dryRunOption->isValueRequired());
        $this->assertFalse($dryRunOption->getDefault());
    }

    public function testCommandExecutionReturnsSuccess(): void
    {
        $command = self::getService(SinaWeiboOAuth2RefreshTokenCommand::class);
        $commandTester = new CommandTester($command);

        // Execute command with CommandTester
        $result = $commandTester->execute([]);

        $this->assertEquals(0, $result);
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(SinaWeiboOAuth2RefreshTokenCommand::class);
        $this->assertInstanceOf(SinaWeiboOAuth2RefreshTokenCommand::class, $command);

        return new CommandTester($command);
    }
}
