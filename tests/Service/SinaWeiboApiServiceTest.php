<?php

namespace Tests\Tourze\SinaWeiboOAuth2Bundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Token;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboUserInfo;
use Tourze\SinaWeiboOAuth2Bundle\Exception\ApiException;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboUserInfoRepository;
use Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboApiService;

class SinaWeiboApiServiceTest extends TestCase
{
    private SinaWeiboApiService $service;
    private HttpClientInterface|MockObject $httpClient;
    private SinaWeiboUserInfoRepository|MockObject $userInfoRepository;
    private EntityManagerInterface|MockObject $entityManager;
    private LoggerInterface|MockObject $logger;

    protected function setUp(): void
    {
        $this->httpClient = $this->createMock(HttpClientInterface::class);
        $this->userInfoRepository = $this->createMock(SinaWeiboUserInfoRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->service = new SinaWeiboApiService(
            $this->httpClient,
            $this->userInfoRepository,
            $this->entityManager,
            $this->logger
        );
    }

    public function testGetUserInfoSuccess(): void
    {
        $token = new SinaWeiboOAuth2Token();
        $token->setAccessToken('test_access_token')
            ->setWeiboUid('123456789')
            ->setValid(true);

        $apiResponse = [
            'id' => 123456789,
            'idstr' => '123456789',
            'screen_name' => 'TestUser',
            'name' => 'Test User',
            'location' => 'Beijing',
            'description' => 'Test description',
            'url' => 'http://example.com',
            'profile_image_url' => 'http://example.com/avatar.jpg',
            'followers_count' => 100,
            'friends_count' => 50,
            'statuses_count' => 200,
            'favourites_count' => 10,
            'created_at' => 'Mon Aug 08 21:46:22 +0800 2011',
            'following' => false,
            'verified' => false,
            'verified_type' => -1,
            'lang' => 'zh-cn',
            'star' => 0,
            'mbtype' => 0,
            'mbrank' => 0,
            'block_word' => 0,
            'block_app' => 0,
            'credit_score' => 80,
            'urank' => 10,
            'story_read_state' => -1,
            'vclub_member' => 0,
            'is_teenager' => 0,
            'is_guardian' => 0,
            'badge' => []
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($apiResponse);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.weibo.com/2/users/show.json?access_token=test_access_token')
            ->willReturn($response);

        // 模拟用户信息保存
        $savedUserInfo = new SinaWeiboUserInfo();
        $savedUserInfo->fillFromApiResponse($apiResponse);

        $this->userInfoRepository->expects($this->once())
            ->method('saveOrUpdate')
            ->willReturn($savedUserInfo);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $userInfo = $this->service->getUserInfo($token);

        $this->assertEquals('123456789', $userInfo->getWeiboUid());
        $this->assertEquals('TestUser', $userInfo->getScreenName());
        $this->assertEquals('Test User', $userInfo->getName());
        $this->assertEquals(100, $userInfo->getFollowersCount());
    }

    public function testIsTokenValidTrue(): void
    {
        $token = new SinaWeiboOAuth2Token();
        $token->setAccessToken('valid_token')
            ->setValid(true);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn(['uid' => '123456789']);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.weibo.com/2/account/verify_credentials.json?access_token=valid_token')
            ->willReturn($response);

        $isValid = $this->service->isTokenValid($token);
        $this->assertTrue($isValid);
    }

    public function testIsTokenValidFalse(): void
    {
        $token = new SinaWeiboOAuth2Token();
        $token->setAccessToken('invalid_token')
            ->setValid(true);

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn(['error' => 'invalid_token', 'error_code' => 21332]);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.weibo.com/2/account/verify_credentials.json?access_token=invalid_token')
            ->willReturn($response);

        $isValid = $this->service->isTokenValid($token);
        $this->assertFalse($isValid);
    }

    public function testUpdateStatusSuccess(): void
    {
        $token = new SinaWeiboOAuth2Token();
        $token->setAccessToken('test_token')
            ->setValid(true);

        $content = 'Test weibo content';
        $apiResponse = [
            'created_at' => 'Sat Aug 08 21:46:22 +0800 2020',
            'id' => 4567890123456789,
            'idstr' => '4567890123456789',
            'text' => $content,
            'source' => 'My App',
            'favorited' => false,
            'truncated' => false,
            'reposts_count' => 0,
            'comments_count' => 0,
            'attitudes_count' => 0
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('getStatusCode')
            ->willReturn(200);
        $response->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($apiResponse);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with(
                'POST',
                'https://api.weibo.com/2/statuses/update.json',
                $this->callback(function ($options) {
                    return isset($options['body']) &&
                        is_array($options['body']) &&
                        $options['body']['access_token'] === 'test_token' &&
                        $options['body']['status'] === 'Test weibo content';
                })
            )
            ->willReturn($response);

        $result = $this->service->updateStatus($token, $content);

        $this->assertEquals('4567890123456789', $result['idstr']);
        $this->assertEquals($content, $result['text']);
    }

    public function testApiException(): void
    {
        $token = new SinaWeiboOAuth2Token();
        $token->setAccessToken('test_token')
            ->setValid(true);

        $errorResponse = [
            'error' => 'invalid_token',
            'error_code' => 21332,
            'request' => '/2/users/show.json'
        ];

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->once())
            ->method('toArray')
            ->with(false)
            ->willReturn($errorResponse);

        $this->httpClient->expects($this->once())
            ->method('request')
            ->with('GET', 'https://api.weibo.com/2/users/show.json?access_token=test_token')
            ->willReturn($response);

        $this->expectException(ApiException::class);
        $this->expectExceptionMessage('invalid_token');
        $this->service->getUserInfo($token);
    }
}
