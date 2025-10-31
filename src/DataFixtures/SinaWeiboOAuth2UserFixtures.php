<?php

namespace Tourze\SinaWeiboOAuth2Bundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Faker\Generator;
use Symfony\Component\DependencyInjection\Attribute\When;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;

#[When(env: 'test')]
#[When(env: 'dev')]
class SinaWeiboOAuth2UserFixtures extends Fixture implements DependentFixtureInterface
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

        $users = [
            [
                'uid' => '1234567890',
                'accessToken' => 'test_access_token_001',
                'expiresIn' => 86400,
                'refreshToken' => 'test_refresh_token_001',
                'nickname' => '测试用户1',
                'avatar' => '/avatar/1.jpg',
                'gender' => 'm',
                'location' => '北京',
                'description' => '这是一个测试用户',
            ],
            [
                'uid' => '0987654321',
                'accessToken' => 'test_access_token_002',
                'expiresIn' => -3600,
                'refreshToken' => null,
                'nickname' => '测试用户2',
                'avatar' => null,
                'gender' => 'f',
                'location' => '上海',
                'description' => null,
            ],
        ];

        foreach ($users as $index => $userData) {
            $user = new SinaWeiboOAuth2User();
            $user->setUid($userData['uid']);
            $user->setAccessToken($userData['accessToken']);
            $user->setExpiresIn($userData['expiresIn']);
            $user->setConfig($config);

            if (null !== $userData['refreshToken']) {
                $user->setRefreshToken($userData['refreshToken']);
            }

            $user->setNickname($userData['nickname']);
            $user->setAvatar($userData['avatar']);
            $user->setGender($userData['gender']);
            $user->setLocation($userData['location']);
            $user->setDescription($userData['description']);

            $rawData = [
                'id' => (int) $userData['uid'],
                'screen_name' => $userData['nickname'],
                'name' => $userData['nickname'],
                'profile_image_url' => $userData['avatar'],
                'gender' => $userData['gender'],
                'location' => $userData['location'],
                'description' => $userData['description'],
                'followers_count' => $this->faker->numberBetween(0, 10000),
                'friends_count' => $this->faker->numberBetween(0, 5000),
                'statuses_count' => $this->faker->numberBetween(0, 50000),
            ];

            $user->setRawData($rawData);

            $manager->persist($user);
            $this->addReference(sprintf('user-%d', $index), $user);
        }

        for ($i = 0; $i < 8; ++$i) {
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

            $uid = (string) $this->faker->numberBetween(1000000000, 9999999999);
            $user = new SinaWeiboOAuth2User();
            $user->setUid($uid);
            $user->setAccessToken($this->faker->regexify('[a-zA-Z0-9]{64}'));
            $user->setExpiresIn($this->faker->numberBetween(-7200, 172800));
            $user->setConfig($randomConfig);

            if ($this->faker->boolean(70)) {
                $user->setRefreshToken($this->faker->regexify('[a-zA-Z0-9]{64}'));
            }

            $user->setNickname($this->faker->userName);
            $user->setAvatar($this->faker->boolean(80) ? sprintf('/avatar/%d.jpg', $this->faker->numberBetween(1, 20)) : null);
            $user->setGender($this->faker->randomElement(['m', 'f', 'n']));
            $user->setLocation($this->faker->city);
            $user->setDescription($this->faker->boolean(60) ? $this->faker->sentence : null);

            $rawData = [
                'id' => (int) $uid,
                'screen_name' => $user->getNickname(),
                'name' => $user->getNickname(),
                'profile_image_url' => $user->getAvatar(),
                'gender' => $user->getGender(),
                'location' => $user->getLocation(),
                'description' => $user->getDescription(),
                'followers_count' => $this->faker->numberBetween(0, 10000),
                'friends_count' => $this->faker->numberBetween(0, 5000),
                'statuses_count' => $this->faker->numberBetween(0, 50000),
                'verified' => $this->faker->boolean(20),
                'created_at' => $this->faker->dateTimeThisDecade->format('D M d H:i:s O Y'),
            ];

            $user->setRawData($rawData);

            $manager->persist($user);
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
