# SinaWeiboOAuth2Bundle

新浪微博OAuth2授权Bundle，提供完整的微博OAuth2授权流程实现，支持用户身份认证、访问令牌管理和微博API调用。

## 功能特性

- ✅ 完整的OAuth2授权流程（授权URL生成、回调处理、令牌获取）
- ✅ 访问令牌自动刷新机制
- ✅ 用户信息获取和管理
- ✅ 微博API调用（发送微博、获取时间线、关注/粉丝管理）
- ✅ 多应用配置支持（实体驱动的配置管理）
- ✅ 完整的事件系统（授权成功/失败事件）
- ✅ 敏感信息加密存储
- ✅ 完善的异常处理和日志记录

## 安装

```bash
composer require tourze/sina-weibo-oauth2-bundle
```

## 配置

### 1. 注册Bundle

```php
// config/bundles.php
return [
    // ...
    Tourze\SinaWeiboOAuth2Bundle\SinaWeiboOAuth2Bundle::class => ['all' => true],
];
```

### 2. 创建应用配置

首先创建新浪微博应用配置：

```php
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboAppConfig;

$appConfig = new SinaWeiboAppConfig();
$appConfig->setAppKey('your_app_key')
    ->setAppSecret('your_app_secret')
    ->setRedirectUri('https://your-domain.com/oauth/callback')
    ->setScope('email,follow_app_official_microblog')
    ->setValid(true);

$entityManager->persist($appConfig);
$entityManager->flush();
```

## 基本使用

### 1. 开始OAuth2授权流程

```php
use Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service;

class OAuth2Controller
{
    public function __construct(
        private readonly SinaWeiboOAuth2Service $weiboOAuth2Service
    ) {}

    public function authorize(): Response
    {
        $appKey = 'your_app_key';
        
        // 生成授权URL和state参数
        $authData = $this->weiboOAuth2Service->startAuthorization($appKey);
        
        // 保存state到session（用于防CSRF攻击）
        $this->session->set('weibo_oauth_state', $authData['state']);
        
        // 重定向用户到微博授权页面
        return $this->redirect($authData['url']);
    }
}
```

### 2. 处理授权回调

```php
public function callback(Request $request): Response
{
    $appKey = 'your_app_key';
    $callbackParams = $request->query->all();
    $expectedState = $this->session->get('weibo_oauth_state');
    
    try {
        // 完成授权流程，获取令牌和用户信息
        $result = $this->weiboOAuth2Service->completeAuthorization(
            $appKey,
            $callbackParams,
            $expectedState,
            $this->getUser()?->getId() // 可选：关联到当前用户
        );
        
        $token = $result['token'];      // SinaWeiboOAuth2Token
        $userInfo = $result['user_info']; // SinaWeiboUserInfo
        
        // 清除session中的state
        $this->session->remove('weibo_oauth_state');
        
        return $this->json([
            'success' => true,
            'weibo_uid' => $userInfo->getWeiboUid(),
            'screen_name' => $userInfo->getScreenName(),
        ]);
        
    } catch (\Exception $e) {
        return $this->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 400);
    }
}
```

### 3. 使用访问令牌调用API

```php
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2TokenRepository;

public function postWeibo(SinaWeiboOAuth2TokenRepository $tokenRepository): Response
{
    // 获取用户的访问令牌
    $token = $tokenRepository->findValidTokenByUser($this->getUser());
    
    if (!$token) {
        return $this->json(['error' => '未找到有效的授权令牌'], 401);
    }
    
    try {
        // 确保令牌有效（自动刷新）
        $validToken = $this->weiboOAuth2Service->ensureValidToken($token);
        
        // 发送微博
        $result = $this->weiboOAuth2Service->postWeibo($validToken, '通过API发送的微博内容');
        
        return $this->json([
            'success' => true,
            'weibo_id' => $result['idstr']
        ]);
        
    } catch (\Exception $e) {
        return $this->json(['error' => $e->getMessage()], 400);
    }
}
```

## 高级功能

### 事件系统

监听OAuth2授权事件：

```php
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Tourze\SinaWeiboOAuth2Bundle\Event\OAuth2AuthorizeSuccessEvent;
use Tourze\SinaWeiboOAuth2Bundle\Event\OAuth2AuthorizeFailureEvent;

class OAuth2EventSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            OAuth2AuthorizeSuccessEvent::NAME => 'onAuthSuccess',
            OAuth2AuthorizeFailureEvent::NAME => 'onAuthFailure',
        ];
    }

    public function onAuthSuccess(OAuth2AuthorizeSuccessEvent $event): void
    {
        $token = $event->getToken();
        $userInfo = $event->getUserInfo();
        
        // 处理授权成功逻辑
        // 例如：发送欢迎邮件、记录日志等
    }

    public function onAuthFailure(OAuth2AuthorizeFailureEvent $event): void
    {
        $exception = $event->getException();
        
        // 处理授权失败逻辑
        // 例如：记录错误日志、发送警报等
    }
}
```

### 同步用户统计信息

使用内置命令同步微博用户统计数据：

```bash
# 同步所有用户的统计信息
php bin/console sina-weibo:sync-user-info

# 同步指定用户的统计信息
php bin/console sina-weibo:sync-user-info --uid=123456789

# 只同步最近30天内活跃的用户
php bin/console sina-weibo:sync-user-info --since="30 days ago"

# 限制同步数量并显示详细输出
php bin/console sina-weibo:sync-user-info --limit=100 -v

# 试运行模式（不实际保存数据）
php bin/console sina-weibo:sync-user-info --dry-run
```

### 多应用支持

支持多个微博应用配置：

```php
// 为不同业务场景创建不同的应用配置
$appConfig1 = new SinaWeiboAppConfig();
$appConfig1->setAppKey('business_app_key')
    ->setAppName('业务应用')
    ->setScope('email,statuses_to_me_write');

$appConfig2 = new SinaWeiboAppConfig();
$appConfig2->setAppKey('marketing_app_key')
    ->setAppName('营销应用')
    ->setScope('friendships_groups_read,friendships_groups_write');
```

## API参考

### 主要服务类

- `SinaWeiboOAuth2Service`: 统一门面服务，提供所有OAuth2操作
- `OAuth2AuthorizeService`: 授权URL生成和回调处理
- `OAuth2TokenService`: 访问令牌获取和刷新
- `SinaWeiboApiService`: 微博API调用

### 实体类

- `SinaWeiboAppConfig`: 应用配置信息
- `SinaWeiboOAuth2Token`: OAuth2访问令牌
- `SinaWeiboUserInfo`: 微博用户信息

### 异常类

- `AuthorizationException`: OAuth2授权相关异常
- `ApiException`: 微博API调用异常

## 安全建议

1. **State参数验证**: 始终使用state参数防止CSRF攻击
2. **HTTPS传输**: 生产环境中必须使用HTTPS
3. **令牌加密**: 敏感信息（访问令牌、刷新令牌）将自动加密存储
4. **权限最小化**: 只请求应用实际需要的API权限

## 故障排除

### 常见问题

1. **授权码无效**: 检查应用配置的回调URL是否与微博开放平台设置一致
2. **访问令牌过期**: 使用`ensureValidToken()`方法自动刷新令牌
3. **API调用失败**: 检查应用权限和API调用频率限制

### 调试模式

启用详细日志记录：

```yaml
# config/packages/dev/monolog.yaml
monolog:
    handlers:
        main:
            type: stream
            path: "%kernel.logs_dir%/%kernel.environment%.log"
            level: info
            channels: ["!event"]
```

## 许可证

MIT License

## 贡献

欢迎提交Issue和Pull Request！
