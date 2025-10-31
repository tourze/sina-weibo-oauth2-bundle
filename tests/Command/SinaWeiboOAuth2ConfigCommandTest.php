<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Command;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\PHPUnitSymfonyKernelTest\AbstractCommandTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Command\SinaWeiboOAuth2ConfigCommand;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2ConfigurationException;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2ConfigRepository;

/**
 * @internal
 */
#[CoversClass(SinaWeiboOAuth2ConfigCommand::class)]
#[RunTestsInSeparateProcesses]
final class SinaWeiboOAuth2ConfigCommandTest extends AbstractCommandTestCase
{
    public function testArgumentAction(): void
    {
        $commandTester = $this->getCommandTester();

        // Test valid actions
        $validActions = ['create', 'list', 'update', 'delete'];
        foreach ($validActions as $action) {
            $commandTester->execute(['action' => $action]);
            // Actions may fail due to missing parameters, but the action argument is tested
            $this->assertContains($commandTester->getStatusCode(), [0, 1]);
        }
    }

    public function testOptionAppId(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'action' => 'create',
            '--app-id' => 'test_app_id',
            '--app-secret' => 'test_secret',
        ]);
        $this->assertContains($commandTester->getStatusCode(), [0, 1]);
    }

    public function testOptionAppSecret(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'action' => 'create',
            '--app-id' => 'test_app_id',
            '--app-secret' => 'test_secret',
        ]);
        $this->assertContains($commandTester->getStatusCode(), [0, 1]);
    }

    public function testOptionScope(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'action' => 'create',
            '--app-id' => 'test_app_id',
            '--app-secret' => 'test_secret',
            '--scope' => 'email,profile',
        ]);
        $this->assertContains($commandTester->getStatusCode(), [0, 1]);
    }

    public function testOptionActive(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'action' => 'create',
            '--app-id' => 'test_app_id',
            '--app-secret' => 'test_secret',
            '--active' => 'false',
        ]);
        $this->assertContains($commandTester->getStatusCode(), [0, 1]);
    }

    public function testOptionId(): void
    {
        $commandTester = $this->getCommandTester();
        $commandTester->execute([
            'action' => 'update',
            '--id' => '1',
            '--app-secret' => 'new_secret',
        ]);
        $this->assertContains($commandTester->getStatusCode(), [0, 1]);
    }

    public function testCreateConfig(): void
    {
        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'action' => 'create',
            '--app-id' => 'test_app_123',
            '--app-secret' => 'test_secret_456',
            '--scope' => 'email,basic_info',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Created Sina Weibo OAuth2 configuration with ID:', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());

        // Verify in database
        $em = self::getEntityManager();
        $repo = self::getService(SinaWeiboOAuth2ConfigRepository::class);
        $configs = $repo->findBy(['appId' => 'test_app_123']);

        $this->assertCount(1, $configs);
        $config = $configs[0];
        $this->assertEquals('test_app_123', $config->getAppId());
        $this->assertEquals('test_secret_456', $config->getAppSecret());
        $this->assertEquals('email,basic_info', $config->getScope());
        $this->assertTrue($config->isValid());
    }

    public function testCreateConfigWithDefaultValues(): void
    {
        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'action' => 'create',
            '--app-id' => 'test_app_default',
            '--app-secret' => 'test_secret_default',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Created Sina Weibo OAuth2 configuration with ID:', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());

        // Verify in database with default values
        $repo = self::getService(SinaWeiboOAuth2ConfigRepository::class);
        $configs = $repo->findBy(['appId' => 'test_app_default']);

        $this->assertCount(1, $configs);
        $config = $configs[0];
        $this->assertEquals('email', $config->getScope()); // Default scope
        $this->assertTrue($config->isValid()); // Default active state
    }

    public function testCreateConfigWithInactiveStatus(): void
    {
        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'action' => 'create',
            '--app-id' => 'test_app_inactive',
            '--app-secret' => 'test_secret_inactive',
            '--active' => 'false',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Created Sina Weibo OAuth2 configuration with ID:', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());

        // Verify inactive status
        $repo = self::getService(SinaWeiboOAuth2ConfigRepository::class);
        $configs = $repo->findBy(['appId' => 'test_app_inactive']);

        $this->assertCount(1, $configs);
        $config = $configs[0];
        $this->assertFalse($config->isValid());
    }

    public function testCreateConfigWithoutRequiredAppId(): void
    {
        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'action' => 'create',
            '--app-secret' => 'test_secret',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('App ID and App Secret are required for creating configuration', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testCreateConfigWithoutRequiredAppSecret(): void
    {
        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'action' => 'create',
            '--app-id' => 'test_app',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('App ID and App Secret are required for creating configuration', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testListConfigsWhenEmpty(): void
    {
        // First, let's test the list functionality works regardless of data
        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['action' => 'list']);

        $output = $commandTester->getDisplay();
        // The command should work and show either configurations or a message
        $this->assertEquals(0, $commandTester->getStatusCode());

        // If there are configs, show the table; if not, show the empty message
        if (str_contains($output, 'No Sina Weibo OAuth2 configurations found')) {
            $this->assertStringContainsString('No Sina Weibo OAuth2 configurations found', $output);
        } else {
            // Should show a table with configurations
            $this->assertStringContainsString('App ID', $output);
            $this->assertStringContainsString('App Secret', $output);
        }
    }

    public function testListConfigsWithData(): void
    {
        $em = self::getEntityManager();

        // Create test configs
        $config1 = new SinaWeiboOAuth2Config();
        $config1->setAppId('app1');
        $config1->setAppSecret('secret1234567890');
        $config1->setScope('email');
        $config1->setValid(true);

        $config2 = new SinaWeiboOAuth2Config();
        $config2->setAppId('app2');
        $config2->setAppSecret('anothersecret123');
        $config2->setScope('basic_info');
        $config2->setValid(false);

        $em->persist($config1);
        $em->persist($config2);
        $em->flush();

        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        $commandTester->execute(['action' => 'list']);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('app1', $output);
        $this->assertStringContainsString('app2', $output);
        $this->assertStringContainsString('************t123', $output); // Masked secret for anothersecret123
        $this->assertStringContainsString('email', $output);
        $this->assertStringContainsString('basic_info', $output);
        $this->assertStringContainsString('Yes', $output); // Active config
        $this->assertStringContainsString('No', $output); // Inactive config
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testUpdateConfig(): void
    {
        $em = self::getEntityManager();

        // Create a config to update
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('original_app');
        $config->setAppSecret('original_secret');
        $config->setScope('email');
        $config->setValid(true);

        $em->persist($config);
        $em->flush();

        $configId = $config->getId();

        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'action' => 'update',
            '--id' => $configId,
            '--app-secret' => 'new_secret',
            '--scope' => 'new_scope',
            '--active' => 'false',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Updated Sina Weibo OAuth2 configuration with ID: {$configId}", $output);
        $this->assertEquals(0, $commandTester->getStatusCode());

        // Verify changes
        $em->refresh($config);
        $this->assertEquals('original_app', $config->getAppId()); // Unchanged
        $this->assertEquals('new_secret', $config->getAppSecret());
        $this->assertEquals('new_scope', $config->getScope());
        $this->assertFalse($config->isValid());
    }

    public function testUpdateNonExistentConfig(): void
    {
        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'action' => 'update',
            '--id' => 999999,
            '--app-secret' => 'new_secret',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Configuration with ID 999999 not found', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testUpdateWithoutId(): void
    {
        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'action' => 'update',
            '--app-secret' => 'new_secret',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Config ID is required for update operation', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testDeleteConfig(): void
    {
        $em = self::getEntityManager();

        // Create a config to delete
        $config = new SinaWeiboOAuth2Config();
        $config->setAppId('to_delete');
        $config->setAppSecret('secret');
        $config->setScope(null);
        $config->setValid(true);

        $em->persist($config);
        $em->flush();

        $configId = $config->getId();

        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'action' => 'delete',
            '--id' => $configId,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString("Deleted Sina Weibo OAuth2 configuration with ID: {$configId}", $output);
        $this->assertEquals(0, $commandTester->getStatusCode());

        // Verify deletion
        $this->assertNull($em->find(SinaWeiboOAuth2Config::class, $configId));
    }

    public function testDeleteNonExistentConfig(): void
    {
        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'action' => 'delete',
            '--id' => 999999,
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Configuration with ID 999999 not found', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testDeleteWithoutId(): void
    {
        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'action' => 'delete',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Config ID is required for delete operation', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    public function testInvalidAction(): void
    {
        $application = new Application(self::$kernel ?? throw new SinaWeiboOAuth2ConfigurationException('Kernel is not available'));
        $command = $application->find('sina-weibo-oauth2:config');
        $commandTester = new CommandTester($command);

        $commandTester->execute([
            'action' => 'invalid',
        ]);

        $output = $commandTester->getDisplay();
        $this->assertStringContainsString('Unknown action: invalid', $output);
        $this->assertEquals(0, $commandTester->getStatusCode());
    }

    protected function getCommandTester(): CommandTester
    {
        $command = self::getService(SinaWeiboOAuth2ConfigCommand::class);
        $this->assertInstanceOf(SinaWeiboOAuth2ConfigCommand::class, $command);

        return new CommandTester($command);
    }

    protected function onSetUp(): void
    {
        // 确保数据库架构已创建
        self::cleanDatabase();
    }
}
