# Sina Weibo OAuth2 Bundle

[![Latest Version](https://img.shields.io/packagist/v/tourze/sina-weibo-oauth2-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/sina-weibo-oauth2-bundle)
[![PHP Version](https://img.shields.io/badge/php-%5E8.1-blue.svg?style=flat-square)](https://php.net/)
[![Symfony Version](https://img.shields.io/badge/symfony-%5E7.3-purple.svg?style=flat-square)](https://symfony.com/)
[![License](https://img.shields.io/badge/license-MIT-green.svg?style=flat-square)](LICENSE)
[![Build Status](https://github.com/tourze/php-monorepo/workflows/CI/badge.svg)](https://github.com/tourze/php-monorepo/actions)
[![Coverage](https://img.shields.io/badge/coverage-100%25-brightgreen.svg)](https://github.com/tourze/php-monorepo)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/sina-weibo-oauth2-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/sina-weibo-oauth2-bundle)

[English](README.md) | [中文](README.zh-CN.md)

A comprehensive Symfony bundle that provides seamless integration with Sina Weibo OAuth2 authentication, including user management, token handling, and administrative tools.

## Table of Contents

- [Features](#features)
- [Installation](#installation)
- [Quick Start](#quick-start)
- [Advanced Usage](#advanced-usage)
- [Console Commands](#console-commands)
- [System Requirements](#system-requirements)
- [Security](#security)
- [License](#license)

## Features

- ✅ **Complete OAuth2 Flow**: Full implementation of Sina Weibo OAuth2 authentication
- 🏗️ **Doctrine Integration**: Pre-built entities for configurations, users, and states
- 🎛️ **Management Commands**: Console commands for easy configuration and maintenance
- 🧹 **Auto Cleanup**: Automatic cleanup of expired tokens and authentication states
- 🚀 **Ready-to-use Controllers**: Built-in login and callback controllers
- 📊 **Repository Pattern**: Clean data access layer with optimized queries
- 🔒 **Security First**: CSRF protection via state validation and secure token handling
- 🧪 **100% Test Coverage**: Comprehensive unit and integration tests
- 📚 **Bilingual Documentation**: Complete documentation in English and Chinese

## Installation

```bash
composer require tourze/sina-weibo-oauth2-bundle
```

## Quick Start

### 1. Configure OAuth2 Application

Use the configuration command to set up your Sina Weibo OAuth2 application:

```bash
php bin/console sina-weibo-oauth2:config create --app-id=YOUR_APP_ID --app-secret=YOUR_APP_SECRET --scope=email
```

### 2. Basic Usage

The bundle provides two pre-built controllers for OAuth2 flow:

- **Login URL**: `/sina-weibo-oauth2/login` - Redirects to Sina Weibo for authentication
- **Callback URL**: `/sina-weibo-oauth2/callback` - Handles the OAuth2 callback

#### Using Built-in Controllers

Simply redirect users to the login URL:

```php
<?php

use Symfony\Component\HttpFoundation\RedirectResponse;

class YourController
{
    public function login(): RedirectResponse
    {
        // Redirect to the built-in login controller
        return new RedirectResponse('/sina-weibo-oauth2/login');
    }
}
```

#### Custom Implementation

For custom OAuth2 flow:

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
        
        // Process authenticated user
        return new Response('User authenticated: ' . $user->getUid());
    }
}
```

## Advanced Usage

### Custom Configuration

You can programmatically manage OAuth2 configurations:

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

### Error Handling

```php
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2Exception;
use Tourze\SinaWeiboOAuth2Bundle\Exception\SinaWeiboOAuth2ConfigurationException;

try {
    $user = $this->oauth2Service->handleCallback($request);
} catch (SinaWeiboOAuth2ConfigurationException $e) {
    // Handle configuration errors
    $this->logger->error('OAuth2 configuration error: ' . $e->getMessage());
} catch (SinaWeiboOAuth2Exception $e) {
    // Handle OAuth2 API errors
    $this->logger->error('OAuth2 API error: ' . $e->getMessage());
}
```

## Console Commands

### Configuration Management

#### `sina-weibo-oauth2:config`

Manage Sina Weibo OAuth2 configuration.

```bash
# List all configurations
php bin/console sina-weibo-oauth2:config list

# Create a new configuration
php bin/console sina-weibo-oauth2:config create --app-id=YOUR_APP_ID --app-secret=YOUR_APP_SECRET --scope=email --active=true

# Update an existing configuration
php bin/console sina-weibo-oauth2:config update --id=1 --app-id=NEW_APP_ID --scope=profile,email

# Delete a configuration
php bin/console sina-weibo-oauth2:config delete --id=1
```

**Available Options:**
- `--app-id`: Sina Weibo App ID
- `--app-secret`: Sina Weibo App Secret
- `--scope`: OAuth2 scope (default: email)
- `--active`: Configuration status (true/false, default: true)
- `--id`: Configuration ID for update/delete operations

## Data Maintenance

### `sina-weibo-oauth2:cleanup`

Clean up expired OAuth2 states and tokens.

```bash
# Clean up expired data
php bin/console sina-weibo-oauth2:cleanup

# Dry run to see what would be cleaned up
php bin/console sina-weibo-oauth2:cleanup --dry-run
```

**Available Options:**
- `--dry-run`: Show what would be cleaned up without actually doing it

## Token Refresh

### `sina-weibo-oauth2:refresh-tokens`

Refresh expired OAuth2 access tokens.

```bash
# Attempt to refresh expired tokens
php bin/console sina-weibo-oauth2:refresh-tokens

# Dry run to see what would be refreshed
php bin/console sina-weibo-oauth2:refresh-tokens --dry-run
```

**Available Options:**
- `--dry-run`: Show what would be refreshed without actually doing it

**Note:** Sina Weibo API does not support refresh tokens like other OAuth2 providers. 
This command is provided for interface compatibility but will always report 0 refreshed tokens. 
Users must re-authenticate when their tokens expire.

## System Requirements

- **PHP**: 8.1 or higher
- **Symfony**: 7.3 or higher
- **Doctrine ORM**: 3.0 or higher
- **Extensions**: `ext-filter`, `ext-json`
- **HTTP Client**: Any PSR-18 compliant HTTP client

### Included Dependencies

- Symfony Framework Bundle
- Symfony Security Bundle
- Symfony HTTP Client
- Doctrine Bundle
- Doctrine ORM
- PSR Log

## Security

### Important Security Considerations

- **Store secrets securely**: Never hardcode your app secret in source code. 
  Use environment variables or Symfony's secrets management.
- **Validate states**: The bundle automatically validates OAuth2 states to prevent CSRF attacks.
- **Token expiration**: Monitor token expiration and implement proper re-authentication flows.
- **HTTPS only**: Always use HTTPS in production for OAuth2 flows.

### Reporting Security Issues

If you discover a security vulnerability, please create a security advisory on GitHub 
or contact the maintainers directly instead of creating a public issue.

## Contributing

Contributions are welcome! Please read our contributing guidelines before submitting pull requests.

### Development Setup

```bash
# Clone the repository
git clone https://github.com/tourze/php-monorepo.git
cd php-monorepo

# Install dependencies
composer install

# Run tests
./vendor/bin/phpunit packages/sina-weibo-oauth2-bundle/tests

# Run static analysis
php -d memory_limit=2G ./vendor/bin/phpstan analyse packages/sina-weibo-oauth2-bundle
```

### Code Quality

- Follow PSR-12 coding standards
- Maintain 100% test coverage
- Pass PHPStan level 5 analysis
- Document all public APIs

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for version history and breaking changes.

## License

This bundle is released under the MIT License. See [LICENSE](LICENSE) for details.

## Credits

- Built for the Tourze PHP Monorepo project
- Sina Weibo OAuth2 API integration
- Community contributions welcomed