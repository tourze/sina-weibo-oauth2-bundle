<?php

/**
 * 新浪微博OAuth2Bundle配置示例
 * 
 * 本文件展示了如何配置和使用新浪微博OAuth2Bundle的各种功能
 */

use Doctrine\ORM\EntityManagerInterface;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboAppConfig;
use Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service;

class WeiboConfigExample
{
    /**
     * 创建应用配置示例
     */
    public function createAppConfig(EntityManagerInterface $entityManager): SinaWeiboAppConfig
    {
        $appConfig = new SinaWeiboAppConfig();
        
        // 基本配置
        $appConfig->setAppKey('your_app_key_from_weibo_platform')
            ->setAppSecret('your_app_secret_from_weibo_platform')
            ->setAppName('我的应用名称')
            ->setRedirectUri('https://your-domain.com/oauth/weibo/callback');
        
        // 权限范围配置（根据需要选择）
        $appConfig->setScope('email,follow_app_official_microblog,statuses_to_me_read');
        
        // 激活配置
        $appConfig->setValid(true);
        
        // 可选：设置备注信息
        // $appConfig->setRemark('用于用户登录和微博互动的应用配置');
        
        $entityManager->persist($appConfig);
        $entityManager->flush();
        
        return $appConfig;
    }
    
    /**
     * 不同业务场景的应用配置示例
     */
    public function createMultipleAppConfigs(EntityManagerInterface $entityManager): array
    {
        $configs = [];
        
        // 用户登录应用
        $loginApp = new SinaWeiboAppConfig();
        $loginApp->setAppKey('login_app_key')
            ->setAppName('用户登录')
            ->setScope('email')
            ->setRedirectUri('https://your-domain.com/login/weibo/callback')
            ->setValid(true);
        $configs['login'] = $loginApp;
        
        // 内容发布应用
        $publishApp = new SinaWeiboAppConfig();
        $publishApp->setAppKey('publish_app_key')
            ->setAppName('内容发布')
            ->setScope('statuses_to_me_write,upload')
            ->setRedirectUri('https://your-domain.com/publish/weibo/callback')
            ->setValid(true);
        $configs['publish'] = $publishApp;
        
        // 数据分析应用
        $analyticsApp = new SinaWeiboAppConfig();
        $analyticsApp->setAppKey('analytics_app_key')
            ->setAppName('数据分析')
            ->setScope('statuses_to_me_read,friendships_groups_read')
            ->setRedirectUri('https://your-domain.com/analytics/weibo/callback')
            ->setValid(true);
        $configs['analytics'] = $analyticsApp;
        
        foreach ($configs as $config) {
            $entityManager->persist($config);
        }
        $entityManager->flush();
        
        return $configs;
    }
    
    /**
     * OAuth2授权流程完整示例
     */
    public function completeOAuth2Flow(
        SinaWeiboOAuth2Service $weiboService,
        string $appKey,
        array $callbackParams,
        string $expectedState
    ): array {
        try {
            // 步骤1：生成授权URL（通常在控制器中进行）
            $authData = $weiboService->startAuthorization($appKey);
            // 返回: ['url' => '授权URL', 'state' => '随机state值']
            
            // 步骤2：用户访问授权URL并同意授权后，微博会回调到配置的redirect_uri
            // 步骤3：处理回调，获取访问令牌和用户信息
            $result = $weiboService->completeAuthorization(
                $appKey,
                $callbackParams, // ['code' => '授权码', 'state' => 'state值']
                $expectedState
            );
            
            $token = $result['token'];        // SinaWeiboOAuth2Token 实体
            $userInfo = $result['user_info']; // SinaWeiboUserInfo 实体
            
            return [
                'success' => true,
                'weibo_uid' => $userInfo->getWeiboUid(),
                'screen_name' => $userInfo->getScreenName(),
                'followers_count' => $userInfo->getFollowersCount(),
                'access_token_id' => $token->getId()
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * API调用示例
     */
    public function apiExamples(SinaWeiboOAuth2Service $weiboService, $token): array
    {
        $examples = [];
        
        try {
            // 确保令牌有效
            $validToken = $weiboService->ensureValidToken($token);
            
            // 发送微博
            $examples['post_weibo'] = $weiboService->postWeibo(
                $validToken, 
                '这是通过API发送的微博内容 #API测试#'
            );
            
            // 获取用户信息
            $examples['user_info'] = $weiboService->getUserInfoSafely($validToken);
            
            // 获取用户时间线
            $examples['timeline'] = $weiboService->getUserTimeline($validToken, null, 10);
            
            // 获取粉丝列表
            $examples['followers'] = $weiboService->getFollowers($validToken, null, 20);
            
            // 获取关注列表
            $examples['friends'] = $weiboService->getFriends($validToken, null, 20);
            
            // 验证令牌
            $examples['token_valid'] = $weiboService->verifyToken($validToken);
            
        } catch (\Exception $e) {
            $examples['error'] = $e->getMessage();
        }
        
        return $examples;
    }
}

/**
 * 权限范围说明
 * 
 * 常用的scope权限：
 * - email: 获取用户邮箱
 * - follow_app_official_microblog: 关注应用官方微博
 * - statuses_to_me_read: 读取@我的微博
 * - statuses_to_me_write: 发送@我的微博
 * - friendships_groups_read: 读取分组信息
 * - friendships_groups_write: 管理分组
 * - statuses_update: 发送微博
 * - upload: 上传图片
 * 
 * 完整权限列表请参考新浪微博开放平台文档
 */

/**
 * 环境变量配置示例 (.env)
 * 
 * # 新浪微博应用配置
 * WEIBO_APP_KEY=your_app_key
 * WEIBO_APP_SECRET=your_app_secret
 * WEIBO_REDIRECT_URI=https://your-domain.com/oauth/weibo/callback
 * 
 * # 可选：API调用基础URL (通常不需要修改)
 * WEIBO_API_BASE_URL=https://api.weibo.com/2
 */

/**
 * 数据库迁移示例
 * 
 * 需要创建以下数据表：
 * - sina_weibo_app_config: 应用配置表
 * - sina_weibo_oauth2_token: OAuth2令牌表  
 * - sina_weibo_user_info: 用户信息表
 * 
 * 使用Doctrine迁移命令：
 * php bin/console doctrine:migrations:diff
 * php bin/console doctrine:migrations:migrate
 */ 