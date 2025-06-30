<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Integration\Command;

use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Tourze\SinaWeiboOAuth2Bundle\Command\SinaWeiboOAuth2RefreshTokenCommand;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;
use Tourze\SinaWeiboOAuth2Bundle\Tests\TestKernel;

class SinaWeiboOAuth2RefreshTokenCommandTest extends KernelTestCase
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

    public function testExecute(): void
    {
        $command = self::getContainer()->get(SinaWeiboOAuth2RefreshTokenCommand::class);
        
        $commandTester = new CommandTester($command);
        $result = $commandTester->execute([]);
        
        $this->assertEquals(0, $result);
        $this->assertStringContainsString('No tokens were refreshed', $commandTester->getDisplay());
    }
}