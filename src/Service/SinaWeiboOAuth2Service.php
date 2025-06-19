<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2ApiException;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2ConfigurationException;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2Exception;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2ConfigRepository;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2StateRepository;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2UserRepository;

class SinaWeiboOAuth2Service
{
    private const AUTHORIZE_URL = 'https://api.weibo.com/oauth2/authorize';
    private const TOKEN_URL = 'https://api.weibo.com/oauth2/access_token';
    private const USER_INFO_URL = 'https://api.weibo.com/2/users/show.json';
    private const DEFAULT_TIMEOUT = 30;
    private const MAX_RETRY_ATTEMPTS = 3;

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SinaWeiboOAuth2ConfigRepository $configRepository,
        private readonly SinaWeiboOAuth2StateRepository $stateRepository,
        private readonly SinaWeiboOAuth2UserRepository $userRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ?LoggerInterface $logger = null
    ) {
    }

    public function generateAuthorizationUrl(?string $sessionId = null): string
    {
        $config = $this->configRepository->findValidConfig();
        if ($config === null) {
            throw new SinaWeiboOAuth2ConfigurationException('No valid Sina Weibo OAuth2 configuration found');
        }

        $state = bin2hex(random_bytes(16));
        $stateEntity = new SinaWeiboOAuth2State($state, $config);
        
        if ($sessionId !== null) {
            $stateEntity->setSessionId($sessionId);
        }
        
        $this->entityManager->persist($stateEntity);
        $this->entityManager->flush();

        $redirectUri = $this->urlGenerator->generate('sina_weibo_oauth2_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        
        $params = [
            'response_type' => 'code',
            'client_id' => $config->getAppId(),
            'redirect_uri' => $redirectUri,
            'state' => $state,
            'scope' => $config->getScope() ?: 'email',
        ];

        return self::AUTHORIZE_URL . '?' . http_build_query($params);
    }

    public function handleCallback(string $code, string $state): SinaWeiboOAuth2User
    {
        $stateEntity = $this->stateRepository->findValidState($state);
        if ($stateEntity === null || !$stateEntity->isValid()) {
            throw new SinaWeiboOAuth2Exception('Invalid or expired state', 0, null, ['state' => $state]);
        }

        $stateEntity->markAsUsed();
        $this->entityManager->persist($stateEntity);
        $this->entityManager->flush();

        // Get config from state
        $config = $stateEntity->getConfig();
        
        // Generate redirect URI
        $redirectUri = $this->urlGenerator->generate('sina_weibo_oauth2_callback', [], UrlGeneratorInterface::ABSOLUTE_URL);
        
        // Exchange code for access token
        $tokenData = $this->exchangeCodeForToken($code, $config->getAppId(), $config->getAppSecret(), $redirectUri);
        
        // Get user info
        $userInfo = $this->fetchUserInfo($tokenData['access_token'], $tokenData['uid']);
        
        // Merge all data
        $userData = array_merge($tokenData, $userInfo);
        
        $user = $this->userRepository->updateOrCreate($userData, $config);
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $user;
    }

    /**
     * @return array<string, mixed>
     */
    private function exchangeCodeForToken(string $code, string $appId, string $appSecret, string $redirectUri): array
    {
        try {
            $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                'body' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => $appId,
                    'client_secret' => $appSecret,
                    'code' => $code,
                    'redirect_uri' => $redirectUri,
                ],
                'timeout' => self::DEFAULT_TIMEOUT,
                'headers' => [
                    'User-Agent' => 'SinaWeiboOAuth2Bundle/1.0',
                    'Accept' => 'application/json',
                ],
            ]);

            /** @var array<string, mixed> $data */
            $data = json_decode($response->getContent(), true);
        } catch (HttpExceptionInterface $e) {
            $this->logger?->error('Sina Weibo OAuth2 token exchange HTTP error', [
                'error' => $e->getMessage(),
                'status_code' => $e->getResponse()->getStatusCode(),
            ]);
            throw new SinaWeiboOAuth2ApiException(
                'Failed to communicate with Sina Weibo API for token exchange',
                0,
                $e,
                self::TOKEN_URL,
                null
            );
        } catch (\Exception $e) {
            $this->logger?->error('Sina Weibo OAuth2 token exchange error', ['error' => $e->getMessage()]);
            throw new SinaWeiboOAuth2ApiException(
                'Network error during token exchange',
                0,
                $e,
                self::TOKEN_URL,
                null
            );
        }

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SinaWeiboOAuth2ApiException('Failed to parse token response', 0, null, self::TOKEN_URL, null);
        }

        if (isset($data['error'])) {
            $this->logger?->warning('Sina Weibo OAuth2 token exchange API error', [
                'error' => $data['error'],
                'error_description' => $data['error_description'] ?? '',
            ]);
            throw new SinaWeiboOAuth2ApiException(
                sprintf('Failed to exchange code for token: %s - %s', $data['error'], $data['error_description'] ?? ''),
                0,
                null,
                self::TOKEN_URL,
                $data
            );
        }

        if (!isset($data['access_token']) || empty($data['access_token'])) {
            $this->logger?->error('Sina Weibo OAuth2 no access token received', [
                'response' => substr($response->getContent(), 0, 200),
            ]);
            throw new SinaWeiboOAuth2ApiException(
                'No access token received from Sina Weibo API',
                0,
                null,
                self::TOKEN_URL,
                $data
            );
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchUserInfo(string $accessToken, string $uid): array
    {
        $response = $this->httpClient->request('GET', self::USER_INFO_URL, [
            'query' => [
                'access_token' => $accessToken,
                'uid' => $uid,
            ],
            'timeout' => self::DEFAULT_TIMEOUT,
            'headers' => [
                'User-Agent' => 'SinaWeiboOAuth2Bundle/1.0',
                'Accept' => 'application/json',
            ],
        ]);

        /** @var array<string, mixed> $data */
        $data = json_decode($response->getContent(), true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new SinaWeiboOAuth2ApiException('Failed to parse user info response', 0, null, self::USER_INFO_URL, null);
        }
        
        if (isset($data['error'])) {
            throw new SinaWeiboOAuth2ApiException(
                sprintf('Failed to get user info: %s - %s', $data['error'], $data['error_description'] ?? ''),
                0,
                null,
                self::USER_INFO_URL,
                $data
            );
        }
        
        return $data;
    }

    /**
     * @return array<string, mixed>
     */
    public function getUserInfo(string $uid, bool $forceRefresh = false): array
    {
        $user = $this->userRepository->findByUid($uid);
        if ($user === null) {
            throw new SinaWeiboOAuth2Exception('User not found', 0, null, ['uid' => $uid]);
        }

        if (!$forceRefresh && !$user->isTokenExpired() && $user->getRawData() !== null) {
            return $user->getRawData();
        }

        if ($user->isTokenExpired() && $user->getRefreshToken() !== null) {
            $this->refreshToken($uid);
            $user = $this->userRepository->findByUid($uid);
        }

        $config = $user->getConfig();
        $userInfo = $this->fetchUserInfo($user->getAccessToken(), $uid);
        
        $user->setNickname($userInfo['screen_name'] ?? $userInfo['name'] ?? null)
            ->setAvatar($userInfo['avatar_large'] ?? $userInfo['profile_image_url'] ?? null)
            ->setGender($userInfo['gender'] ?? null)
            ->setLocation($userInfo['location'] ?? null)
            ->setDescription($userInfo['description'] ?? null)
            ->setRawData($userInfo);
            
        $this->entityManager->persist($user);
        $this->entityManager->flush();
        
        return $userInfo;
    }

    public function refreshToken(string $uid): bool
    {
        // Note: Sina Weibo API doesn't support refresh tokens in the same way as other OAuth2 providers
        // This method is kept for interface compatibility but will return false
        // Users need to re-authenticate when their tokens expire
        return false;
    }

    public function refreshExpiredTokens(): int
    {
        $expiredUsers = $this->userRepository->findExpiredTokenUsers();
        $refreshed = 0;

        foreach ($expiredUsers as $user) {
            if ($this->refreshToken($user->getUid())) {
                $refreshed++;
            }

            // Add small delay to avoid rate limiting
            usleep(100000); // 0.1 seconds
        }

        return $refreshed;
    }

    public function cleanupExpiredStates(): int
    {
        return $this->stateRepository->cleanupExpiredStates();
    }

    private function executeWithRetry(callable $operation, int $maxRetries = self::MAX_RETRY_ATTEMPTS): mixed
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            try {
                return $operation();
            } catch (HttpExceptionInterface $e) {
                $lastException = $e;

                // Don't retry on client errors (4xx)
                if ($e->getResponse()->getStatusCode() >= 400 && $e->getResponse()->getStatusCode() < 500) {
                    throw $e;
                }

                // Only retry on server errors (5xx) or network issues
                if ($attempt < $maxRetries) {
                    $this->logger?->warning('Sina Weibo OAuth2 request failed, retrying', [
                        'attempt' => $attempt,
                        'max_retries' => $maxRetries,
                        'error' => $e->getMessage(),
                    ]);

                    // Exponential backoff: 1s, 2s, 4s
                    sleep(2 ** ($attempt - 1));
                }
            } catch (\Exception $e) {
                $lastException = $e;
                if ($attempt < $maxRetries) {
                    $this->logger?->warning('Sina Weibo OAuth2 request failed, retrying', [
                        'attempt' => $attempt,
                        'max_retries' => $maxRetries,
                        'error' => $e->getMessage(),
                    ]);
                    sleep(1);
                }
            }
        }

        throw $lastException;
    }
}
