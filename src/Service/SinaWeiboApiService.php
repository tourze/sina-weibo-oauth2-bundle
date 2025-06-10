<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Token;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboUserInfo;
use Tourze\SinaWeiboOAuth2Bundle\Exception\ApiException;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboUserInfoRepository;

/**
 * 新浪微博API服务
 * 负责使用访问令牌调用微博API
 */
class SinaWeiboApiService
{
    /**
     * 新浪微博API基础URL
     */
    private const API_BASE_URL = 'https://api.weibo.com/2';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly SinaWeiboUserInfoRepository $userInfoRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * 获取用户基本信息
     *
     * @param SinaWeiboOAuth2Token $token 访问令牌
     * @param string|null $uid 用户UID（可选，默认获取当前授权用户信息）
     * @throws ApiException
     */
    public function getUserInfo(SinaWeiboOAuth2Token $token, ?string $uid = null): SinaWeiboUserInfo
    {
        $params = [
            'access_token' => $token->getAccessToken() // TODO: 解密
        ];

        if ($uid !== null) {
            $params['uid'] = $uid;
        }

        $userData = $this->makeApiRequest('GET', '/users/show.json', $params);

        // 创建或更新用户信息实体
        $userInfo = new SinaWeiboUserInfo();
        $userInfo->fillFromApiResponse($userData);

        try {
            // 保存或更新到数据库
            $savedUserInfo = $this->userInfoRepository->saveOrUpdate($userInfo);
            $this->entityManager->flush();

            $this->logger->info('User info retrieved and saved successfully', [
                'weibo_uid' => $userInfo->getWeiboUid(),
                'screen_name' => $userInfo->getScreenName()
            ]);

            return $savedUserInfo;
        } catch (\Exception $e) {
            $this->logger->error('Failed to save user info', [
                'weibo_uid' => $userInfo->getWeiboUid(),
                'error' => $e->getMessage(),
                'exception' => $e
            ]);
            throw $e;
        }
    }

    /**
     * 验证访问令牌有效性
     *
     * @throws ApiException
     */
    public function verifyCredentials(SinaWeiboOAuth2Token $token): array
    {
        $params = [
            'access_token' => $token->getAccessToken() // TODO: 解密
        ];

        return $this->makeApiRequest('GET', '/account/verify_credentials.json', $params);
    }

    /**
     * 获取用户最新微博
     *
     * @param SinaWeiboOAuth2Token $token 访问令牌
     * @param string|null $uid 用户UID
     * @param int $count 返回条数（最大200）
     * @throws ApiException
     */
    public function getUserTimeline(SinaWeiboOAuth2Token $token, ?string $uid = null, int $count = 20): array
    {
        $params = [
            'access_token' => $token->getAccessToken(), // TODO: 解密
            'count' => min($count, 200)
        ];

        if ($uid !== null) {
            $params['uid'] = $uid;
        }

        return $this->makeApiRequest('GET', '/statuses/user_timeline.json', $params);
    }

    /**
     * 发送微博
     *
     * @param SinaWeiboOAuth2Token $token 访问令牌
     * @param string $status 微博内容
     * @throws ApiException
     */
    public function updateStatus(SinaWeiboOAuth2Token $token, string $status): array
    {
        $params = [
            'access_token' => $token->getAccessToken(), // TODO: 解密
            'status' => $status
        ];

        return $this->makeApiRequest('POST', '/statuses/update.json', $params);
    }

    /**
     * 获取用户粉丝列表
     *
     * @param SinaWeiboOAuth2Token $token 访问令牌
     * @param string|null $uid 用户UID
     * @param int $count 返回条数（最大200）
     * @param int $cursor 游标
     * @throws ApiException
     */
    public function getFollowers(
        SinaWeiboOAuth2Token $token,
        ?string $uid = null,
        int $count = 20,
        int $cursor = 0
    ): array {
        $params = [
            'access_token' => $token->getAccessToken(), // TODO: 解密
            'count' => min($count, 200),
            'cursor' => $cursor
        ];

        if ($uid !== null) {
            $params['uid'] = $uid;
        }

        return $this->makeApiRequest('GET', '/friendships/followers.json', $params);
    }

    /**
     * 获取用户关注列表
     *
     * @param SinaWeiboOAuth2Token $token 访问令牌
     * @param string|null $uid 用户UID
     * @param int $count 返回条数（最大200）
     * @param int $cursor 游标
     * @throws ApiException
     */
    public function getFriends(
        SinaWeiboOAuth2Token $token,
        ?string $uid = null,
        int $count = 20,
        int $cursor = 0
    ): array {
        $params = [
            'access_token' => $token->getAccessToken(), // TODO: 解密
            'count' => min($count, 200),
            'cursor' => $cursor
        ];

        if ($uid !== null) {
            $params['uid'] = $uid;
        }

        return $this->makeApiRequest('GET', '/friendships/friends.json', $params);
    }

    /**
     * 获取公共微博时间线
     *
     * @param SinaWeiboOAuth2Token $token 访问令牌
     * @param int $count 返回条数（最大200）
     * @throws ApiException
     */
    public function getPublicTimeline(SinaWeiboOAuth2Token $token, int $count = 20): array
    {
        $params = [
            'access_token' => $token->getAccessToken(), // TODO: 解密
            'count' => min($count, 200)
        ];

        return $this->makeApiRequest('GET', '/statuses/public_timeline.json', $params);
    }

    /**
     * 进行API请求
     *
     * @param string $method HTTP方法
     * @param string $endpoint API端点
     * @param array $params 请求参数
     * @throws ApiException
     */
    private function makeApiRequest(string $method, string $endpoint, array $params = []): array
    {
        $url = self::API_BASE_URL . $endpoint;

        try {
            $options = [
                'headers' => [
                    'Accept' => 'application/json',
                    'User-Agent' => 'SinaWeiboOAuth2Bundle/1.0'
                ]
            ];

            if ($method === 'GET') {
                $url .= '?' . http_build_query($params);
            } else {
                $options['body'] = $params;
                $options['headers']['Content-Type'] = 'application/x-www-form-urlencoded';
            }

            $response = $this->httpClient->request($method, $url, $options);
            $responseData = $response->toArray(false);

            // 检查API错误
            if (isset($responseData['error']) || isset($responseData['error_code'])) {
                throw ApiException::fromApiResponse($responseData);
            }

            // 检查HTTP状态码
            if ($response->getStatusCode() >= 400) {
                throw new ApiException(
                    "API请求失败，HTTP状态码: {$response->getStatusCode()}",
                    $response->getStatusCode(),
                    $responseData
                );
            }

            return $responseData;
        } catch (\Exception $e) {
            if (!$e instanceof ApiException) {
                throw new ApiException(
                    "API请求异常: {$e->getMessage()}",
                    0,
                    null,
                    $e
                );
            }
            throw $e;
        }
    }

    /**
     * 检查令牌是否有效（通过API调用验证）
     */
    public function isTokenValid(SinaWeiboOAuth2Token $token): bool
    {
        try {
            $this->verifyCredentials($token);
            return true;
        } catch (ApiException $e) {
            // 检查是否是令牌相关的错误
            $errorCode = $e->getCode();
            if (in_array($errorCode, [21327, 21332, 21314])) { // 令牌过期、无效等错误码
                return false;
            }
            // 其他错误重新抛出
            throw $e;
        }
    }
}
