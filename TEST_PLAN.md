# Sina Weibo OAuth2 Bundle - Test Plan

## 测试覆盖范围

### 1. 实体测试 (Entity Tests)
- **SinaWeiboOAuth2ConfigTest**: 配置实体的构造器、getter/setter、流式接口
- **SinaWeiboOAuth2StateTest**: 状态实体的构造器、有效性检查、过期检查、使用标记
- **SinaWeiboOAuth2UserTest**: 用户实体的构造器、属性管理、令牌过期检查

### 2. 服务测试 (Service Tests)
- **SinaWeiboOAuth2ServiceTest**: 
  - 授权URL生成（成功/失败场景）
  - 回调处理（成功/无效状态）
  - 用户信息获取（存在/不存在用户）
  - 令牌刷新（微博不支持刷新令牌）
  - 过期状态清理

### 3. 功能测试 (Functional Tests)
- **SinaWeiboOAuth2ControllerTest**:
  - 登录重定向到微博授权
  - 回调参数验证（有效/无效/缺失参数）
  - OAuth错误处理
  - 安全参数格式验证

### 4. 仓储测试 (Repository Tests)
- **SinaWeiboOAuth2RepositoryTest**:
  - 配置仓储：查找有效配置、活跃配置列表
  - 状态仓储：查找有效状态、清理过期状态、统计
  - 用户仓储：按UID查找、创建/更新用户、批量操作

### 5. 命令测试 (Command Tests)
- **SinaWeiboOAuth2CommandTest**:
  - 配置管理命令：列表/创建/更新/删除
  - 清理命令：正常/干运行模式
  - 令牌刷新命令：微博不支持刷新令牌的提示

### 6. 集成测试 (Integration Tests)
- **SinaWeiboOAuth2BundleTest**:
  - 服务注册验证
  - Doctrine映射加载
  - 控制台命令注册
  - 路由加载
  - Bundle配置验证

## 测试特殊场景

### 微博 OAuth2 特殊性
1. **无刷新令牌支持**: 微博API不支持刷新令牌，需要重新授权
2. **POST方式获取令牌**: 与QQ不同，微博使用POST请求获取访问令牌
3. **UID字段**: 微博返回的用户唯一标识是`uid`而不是`openid`
4. **用户信息接口**: 需要同时传递`access_token`和`uid`参数

### 安全性测试
1. **参数格式验证**: 验证授权码和状态参数的格式
2. **状态管理**: 验证状态的生成、验证、过期和使用标记
3. **错误处理**: 测试各种API错误和网络错误场景

### 边界条件测试
1. **空配置**: 无有效配置时的错误处理
2. **过期状态**: 过期状态的识别和清理
3. **重复用户**: 同一用户多次授权的更新逻辑
4. **缺失字段**: API返回数据缺失字段的处理

## 运行测试

```bash
# 运行所有测试
vendor/bin/phpunit

# 运行特定测试类
vendor/bin/phpunit tests/Entity/SinaWeiboOAuth2ConfigTest.php

# 运行功能测试
vendor/bin/phpunit tests/Functional/

# 生成覆盖率报告
vendor/bin/phpunit --coverage-html coverage/
```

## 测试数据

### 测试配置
- App ID: `test_app_id`
- App Secret: `test_secret`
- Scope: `email`

### 模拟响应
```json
// 令牌响应
{
  "access_token": "test_token",
  "expires_in": 3600,
  "uid": "test_uid"
}

// 用户信息响应
{
  "id": "test_uid",
  "screen_name": "Test User",
  "profile_image_url": "http://example.com/avatar.jpg",
  "gender": "m",
  "location": "北京"
}
```

## 质量指标

- **代码覆盖率**: 目标 >= 85%
- **测试用例数**: 50+ 个测试方法
- **断言数**: 200+ 个断言
- **边界条件**: 覆盖所有主要错误场景
- **集成测试**: 验证Bundle完整性