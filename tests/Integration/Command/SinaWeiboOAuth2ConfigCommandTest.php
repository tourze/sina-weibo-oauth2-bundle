<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Integration\Command;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\SinaWeiboOAuth2Bundle\Command\SinaWeiboOAuth2ConfigCommand;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;
use Tourze\SinaWeiboOAuth2Bundle\Tests\TestKernel;

class SinaWeiboOAuth2ConfigCommandTest extends KernelTestCase
{
    protected static function getKernelClass(): string
    {
        return TestKernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel();
        $this->setupDatabase();
    }

    private function setupDatabase(): void
    {
        $em = self::getContainer()->get('doctrine')->getManager();
        $schemaTool = new SchemaTool($em);
        
        $classes = [
            $em->getClassMetadata(SinaWeiboOAuth2Config::class),
            $em->getClassMetadata(SinaWeiboOAuth2State::class),
            $em->getClassMetadata(SinaWeiboOAuth2User::class),
        ];
        $schemaTool->dropSchema($classes);
        $schemaTool->createSchema($classes);
    }

    public function testListAction(): void
    {
        $command = self::getContainer()->get(SinaWeiboOAuth2ConfigCommand::class);
        
        $commandTester = new CommandTester($command);
        $result = $commandTester->execute(['action' => 'list']);
        
        $this->assertEquals(0, $result);
    }

    public function testCreateAction(): void
    {
        $command = self::getContainer()->get(SinaWeiboOAuth2ConfigCommand::class);
        
        $commandTester = new CommandTester($command);
        $result = $commandTester->execute([
            'action' => 'create',
            '--app-id' => 'test_app_id',
            '--app-secret' => 'test_secret'
        ]);
        
        $this->assertEquals(0, $result);
    }
}