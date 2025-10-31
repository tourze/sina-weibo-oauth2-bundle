<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Factory;

use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;
use Tourze\SinaWeiboOAuth2Bundle\Exception\InvalidUserDataException;

class SinaWeiboOAuth2UserFactory
{
    /**
     * @param array<string, mixed> $data
     */
    public function createFromData(array $data, SinaWeiboOAuth2Config $config): SinaWeiboOAuth2User
    {
        $uid = $this->extractUid($data);
        $expiresIn = (int) ($data['expires_in'] ?? 3600);

        $user = new SinaWeiboOAuth2User();
        $user->setUid($uid);
        $user->setAccessToken($data['access_token']);
        $user->setExpiresIn($expiresIn);
        $user->setConfig($config);

        $this->updateUserProfile($user, $data);
        $user->setRawData($data);

        return $user;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function extractUid(array $data): string
    {
        $uid = $data['uid'] ?? $data['id'] ?? null;
        if (null === $uid || '' === $uid) {
            throw new InvalidUserDataException('User data must contain uid or id field');
        }

        return $uid;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function updateUserProfile(SinaWeiboOAuth2User $user, array $data): void
    {
        if (isset($data['name'])) {
            $user->setNickname($data['name']);
        }
        if (isset($data['screen_name'])) {
            $user->setNickname($data['screen_name']);
        }
        if (isset($data['profile_image_url'])) {
            $user->setAvatar($data['profile_image_url']);
        }
        if (isset($data['avatar_large'])) {
            $user->setAvatar($data['avatar_large']);
        }
        if (isset($data['gender'])) {
            $user->setGender($data['gender']);
        }
        if (isset($data['location'])) {
            $user->setLocation($data['location']);
        }
        if (isset($data['description'])) {
            $user->setDescription($data['description']);
        }
    }

    /**
     * @param array<string, mixed> $data
     */
    public function updateFromData(SinaWeiboOAuth2User $user, array $data): SinaWeiboOAuth2User
    {
        $user->setAccessToken($data['access_token']);
        if (isset($data['expires_in'])) {
            $user->setExpiresIn((int) $data['expires_in']);
        }
        if (isset($data['refresh_token'])) {
            $user->setRefreshToken($data['refresh_token']);
        }

        $this->updateUserProfile($user, $data);
        $user->setRawData($data);

        return $user;
    }
}
