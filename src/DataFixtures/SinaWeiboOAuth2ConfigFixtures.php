<?php

namespace Tourze\SinaWeiboOAuth2Bundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;

#[When(env: 'test')]
#[When(env: 'dev')]
class SinaWeiboOAuth2ConfigFixtures extends Fixture
{
    public const CONFIG_REFERENCE = 'config';
    public const CONFIG_REFERENCE_1 = 'config-1';
    public const CONFIG_REFERENCE_2 = 'config-2';

    private Generator $faker;

    public function __construct()
    {
        $this->faker = Factory::create('zh_CN');
    }

    public function load(ObjectManager $manager): void
    {
        $configs = [
            [
                'appId' => 'test_app_id_001',
                'appSecret' => 'test_app_secret_001_very_long_string_for_security',
                'scope' => 'email,statuses_to_me,follow_app_official_microblog',
                'valid' => true,
            ],
            [
                'appId' => 'test_app_id_002',
                'appSecret' => 'test_app_secret_002_another_long_string_for_security',
                'scope' => 'statuses_to_me,follow_app_official_microblog',
                'valid' => false,
            ],
            [
                'appId' => 'production_app_id',
                'appSecret' => 'production_app_secret_super_long_secure_string',
                'scope' => null,
                'valid' => true,
            ],
        ];

        foreach ($configs as $index => $configData) {
            $config = new SinaWeiboOAuth2Config();
            $config->setAppId($configData['appId']);
            $config->setAppSecret($configData['appSecret']);
            $config->setScope($configData['scope']);
            $config->setValid($configData['valid']);

            $manager->persist($config);
            $referenceKey = match ($index) {
                0 => self::CONFIG_REFERENCE,
                1 => self::CONFIG_REFERENCE_1,
                2 => self::CONFIG_REFERENCE_2,
            };
            $this->addReference($referenceKey, $config);
        }

        for ($i = 0; $i < 3; ++$i) {
            $config = new SinaWeiboOAuth2Config();
            $config->setAppId($this->faker->regexify('app_[a-z0-9]{16}'));
            $config->setAppSecret($this->faker->regexify('[a-zA-Z0-9]{64}'));
            $config->setScope($this->faker->randomElement([
                'email,statuses_to_me',
                'follow_app_official_microblog',
                null,
            ]));
            $config->setValid($this->faker->boolean(80));

            $manager->persist($config);
            $this->addReference(sprintf('config-random-%d', $i), $config);
        }

        $manager->flush();
    }
}
