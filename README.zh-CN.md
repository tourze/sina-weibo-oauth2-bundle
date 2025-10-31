# Sina Weibo OAuth2 Bundle

[![最新版本](https://img.shields.io/packagist/v/tourze/sina-weibo-oauth2-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/sina-weibo-oauth2-bundle)
[![PHP 版本](https://img.shields.io/badge/php-%5E8.1-blue.svg?style=flat-square)](https://php.net/)
[![Symfony 版本](https://img.shields.io/badge/symfony-%5E7.3-purple.svg?style=flat-square)](https://symfony.com/)
[![许可证](https://img.shields.io/badge/license-MIT-green.svg?style=flat-square)](LICENSE)
[![构建状态](https://github.com/tourze/php-monorepo/workflows/CI/badge.svg)](https://github.com/tourze/php-monorepo/actions)
[![代码覆盖率](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](https://github.com/tourze/php-monorepo)
[![总下载量](https://img.shields.io/packagist/dt/tourze/sina-weibo-oauth2-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/sina-weibo-oauth2-bundle)

[English](README.md) | [中文](README.zh-CN.md)

一个全面的 Symfony Bundle，提供与新浪微博 OAuth2 认证的无缝集成，包括用户管理、令牌处理和管理工具。

## 目录

- [功能特性](#功能特性)
- [安装](#安装)
- [快速开始](#快速开始)
- [高级用法](#高级用法)
- [控制台命令](#控制台命令)
- [系统要求](#系统要求)
- [安全](#安全)
- [许可证](#许可证)

## 功能特性

- ✅ **完整的 OAuth2 流程**：新浪微博 OAuth2 认证的完整实现
- 🏗️ **Doctrine 集成**：预构建的配置、用户和状态实体
- 🎛️ **管理命令**：用于配置和维护的控制台命令
- 🧹 **自动清理**：自动清理过期的令牌和认证状态
- 🚀 **即用控制器**：内置的登录和回调控制器
- 📊 **Repository 模式**：具有优化查询的清洁数据访问层
- 🔒 **安全优先**：通过状态验证和安全令牌处理进行 CSRF 保护
- 🧪 **100% 测试覆盖率**：全面的单元测试和集成测试
- 📚 **双语文档**：完整的中英文文档

## 安装

```bash
composer require tourze/sina-weibo-oauth2-bundle
```

## 快速开始

### 1. 配置 OAuth2 应用

使用配置命令设置您的新浪微博 OAuth2 应用：

```bash
php bin/console sina-weibo-oauth2:config create --app-id=YOUR_APP_ID --app-secret=YOUR_APP_SECRET --scope=email
```

### 2. 基本用法

Bundle 提供两个预构建的控制器用于 OAuth2 流程：

- **登录 URL**: `/sina-weibo-oauth2/login` - 重定向到新浪微博进行认证
- **回调 URL**: `/sina-weibo-oauth2/callback` - 处理 OAuth2 回调

#### 使用内置控制器

只需将用户重定向到登录 URL：

```php
<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

class YourController
{
    public function login(): RedirectResponse
    {
        // 重定向到内置登录控制器
        return new RedirectResponse('/sina-weibo-oauth2/login');
    }
}
```

#### 自定义实现

对于自定义 OAuth2 流程：

```php
<?php

use Tourze\SinaWeiboOAuth2Bundle\Service\SinaWeiboOAuth2Service;
use Symfony\Component\HttpFoundation\Request;

class YourController
{
    public function __construct(
        private SinaWeiboOAuth2Service $oauth2Service,
    ) {
    }

    public function login(Request $request): RedirectResponse
    {
        $sessionId = $request->getSession()->getId();
        $authUrl = $this->oauth2Service->generateAuthorizationUrl($sessionId);
        return new RedirectResponse($authUrl);
    }

    public function callback(Request $request): Response
    {
        $code = $request->query->get('code');
        $state = $request->query->get('state');
        
        $user = $this->oauth2Service->handleCallback($code, $state);
        
        // 处理已认证用户
        return new Response('用户已认证: ' . $user->getUid());
    }
}
```

## 高级用法

### 自定义配置

您可以编程方式管理 OAuth2 配置：

```php
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;
use Tourze\SinaWeiboOAuth2Bundle\Repository\SinaWeiboOAuth2ConfigRepository;

public function createConfig(SinaWeiboOAuth2ConfigRepository $configRepo): void
{
    $config = new SinaWeiboOAuth2Config();
    $config->setAppId('your-app-id')
           ->setAppSecret('your-app-secret')
           ->setScope('email,profile')
           ->setValid(true);
    
    $configRepo->save($config);
}
```

### 错误处理

```php
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2Exception;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2ConfigurationException;

try {
    $user = $this->oauth2Service->handleCallback($request);
} catch (SinaWeiboOAuth2ConfigurationException $e) {
    // 处理配置错误
    $this->logger->error('OAuth2 配置错误: ' . $e->getMessage());
} catch (SinaWeiboOAuth2Exception $e) {
    // 处理 OAuth2 API 错误
    $this->logger->error('OAuth2 API 错误: ' . $e->getMessage());
}
```

## 控制台命令

### 配置管理

#### `sina-weibo-oauth2:config`

管理新浪微博 OAuth2 配置。

```bash
# 列出所有配置
php bin/console sina-weibo-oauth2:config list

# 创建新配置
php bin/console sina-weibo-oauth2:config create --app-id=YOUR_APP_ID --app-secret=YOUR_APP_SECRET --scope=email --active=true

# 更新现有配置
php bin/console sina-weibo-oauth2:config update --id=1 --app-id=NEW_APP_ID --scope=profile,email

# 删除配置
php bin/console sina-weibo-oauth2:config delete --id=1
```

**可用选项：**
- `--app-id`: 新浪微博应用 ID
- `--app-secret`: 新浪微博应用密钥
- `--scope`: OAuth2 作用域（默认：email）
- `--active`: 配置状态（true/false，默认：true）
- `--id`: 用于更新/删除操作的配置 ID

## 数据维护

### `sina-weibo-oauth2:cleanup`

清理过期的 OAuth2 状态和令牌。

```bash
# 清理过期数据
php bin/console sina-weibo-oauth2:cleanup

# 预览运行，查看将要清理的内容
php bin/console sina-weibo-oauth2:cleanup --dry-run
```

**可用选项：**
- `--dry-run`: 显示将要清理的内容，但不实际执行

## 令牌刷新

### `sina-weibo-oauth2:refresh-tokens`

刷新过期的 OAuth2 访问令牌。

```bash
# 尝试刷新过期令牌
php bin/console sina-weibo-oauth2:refresh-tokens

# 预览运行，查看将要刷新的内容
php bin/console sina-weibo-oauth2:refresh-tokens --dry-run
```

**可用选项：**
- `--dry-run`: 显示将要刷新的内容，但不实际执行

**注意：** 新浪微博 API 不像其他 OAuth2 提供商那样支持刷新令牌。此命令是为了接口兼容性而提供的，但总是会报告 0 个刷新的令牌。用户必须在令牌过期时重新认证。

## 系统要求

- **PHP**：8.1 或更高版本
- **Symfony**：7.3 或更高版本
- **Doctrine ORM**：3.0 或更高版本
- **扩展**：`ext-filter`、`ext-json`
- **HTTP 客户端**：任何符合 PSR-18 的 HTTP 客户端

### 包含的依赖项

- Symfony Framework Bundle
- Symfony Security Bundle
- Symfony HTTP Client
- Doctrine Bundle
- Doctrine ORM
- PSR Log

## 安全

### 重要安全注意事项

- **安全存储密钥**：永远不要在源代码中硬编码您的应用密钥。使用环境变量或 Symfony 的密钥管理。
- **验证状态**：Bundle 会自动验证 OAuth2 状态以防止 CSRF 攻击。
- **令牌过期**：监控令牌过期并实现适当的重新认证流程。
- **仅限 HTTPS**：在生产环境中始终对 OAuth2 流程使用 HTTPS。

### 报告安全问题

如果您发现安全漏洞，请在 GitHub 上创建安全公告
或直接联系维护者，而不是创建公开问题。

## 贡献

欢迎贡献！请在提交拉取请求之前阅读我们的贡献指南。

### 开发设置

```bash
# 克隆仓库
git clone https://github.com/tourze/php-monorepo.git
cd php-monorepo

# 安装依赖项
composer install

# 运行测试
./vendor/bin/phpunit packages/sina-weibo-oauth2-bundle/tests

# 运行静态分析
php -d memory_limit=2G ./vendor/bin/phpstan analyse packages/sina-weibo-oauth2-bundle
```

### 代码质量

- 遵循 PSR-12 编码标准
- 维护 100% 测试覆盖率
- 通过 PHPStan level 5 分析
- 记录所有公共 API

## 更新日志

查看 [CHANGELOG.md](CHANGELOG.md) 了解版本历史和重大变更。

## 许可证

此 Bundle 根据 MIT 许可证发布。详情请见 [LICENSE](LICENSE)。

## 致谢

- 为 Tourze PHP Monorepo 项目构建
- 新浪微博 OAuth2 API 集成
- 欢迎社区贡献