<?php

namespace Tourze\SinaWeiboOAuth2Bundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;

#[When(env: 'test')]
#[When(env: 'dev')]
class SinaWeiboOAuth2StateFixtures extends Fixture implements DependentFixtureInterface
{
    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create('zh_CN');
    }

    public function load(ObjectManager $manager): void
    {
        // 尝试获取配置引用，如果不存在则创建
        $config = $this->getReference(SinaWeiboOAuth2ConfigFixtures::CONFIG_REFERENCE, SinaWeiboOAuth2Config::class);
        if (!$config instanceof SinaWeiboOAuth2Config) {
            // 如果引用不存在，创建一个新的配置
            $config = new SinaWeiboOAuth2Config();
            $config->setAppId('test_app_id_001');
            $config->setAppSecret('test_app_secret_001_very_long_string_for_security');
            $config->setScope('email,statuses_to_me,follow_app_official_microblog');
            $config->setValid(true);
            $manager->persist($config);
            $this->addReference(SinaWeiboOAuth2ConfigFixtures::CONFIG_REFERENCE, $config);
        }

        $states = [
            ['state' => 'test_state_001', 'expiresInMinutes' => 10, 'sessionId' => 'sess_001', 'used' => false],
            ['state' => 'test_state_002', 'expiresInMinutes' => -5, 'sessionId' => 'sess_002', 'used' => false],
            ['state' => 'test_state_003', 'expiresInMinutes' => 5, 'sessionId' => null, 'used' => true],
        ];

        foreach ($states as $index => $stateData) {
            $state = new SinaWeiboOAuth2State();
            $state->setState($stateData['state']);
            $state->setConfig($config);
            $state->setExpiresInMinutes($stateData['expiresInMinutes']);

            if (null !== $stateData['sessionId']) {
                $state->setSessionId($stateData['sessionId']);
            }

            if ($stateData['used']) {
                $state->markAsUsed();
            }

            $manager->persist($state);
            $this->addReference(sprintf('state-%d', $index), $state);
        }

        for ($i = 0; $i < 5; ++$i) {
            $configIndex = $this->faker->numberBetween(0, 2);
            $randomConfig = $this->getReference(
                match ($configIndex) {
                    0 => SinaWeiboOAuth2ConfigFixtures::CONFIG_REFERENCE,
                    1 => SinaWeiboOAuth2ConfigFixtures::CONFIG_REFERENCE_1,
                    default => SinaWeiboOAuth2ConfigFixtures::CONFIG_REFERENCE_2,
                },
                SinaWeiboOAuth2Config::class
            );
            assert($randomConfig instanceof SinaWeiboOAuth2Config);

            $state = new SinaWeiboOAuth2State();
            $state->setState($this->faker->regexify('[a-zA-Z0-9]{32}'));
            $state->setConfig($randomConfig);
            $state->setExpiresInMinutes($this->faker->numberBetween(-30, 60));

            if ($this->faker->boolean(70)) {
                $state->setSessionId($this->faker->uuid);
            }

            if ($this->faker->boolean(30)) {
                $state->markAsUsed();
            }

            $manager->persist($state);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            SinaWeiboOAuth2ConfigFixtures::class,
        ];
    }
}
