<?php

namespace Tourze\SinaWeiboOAuth2Bundle\Enum;

/**
 * 性别枚举
 */
enum Gender: string
{
    case MALE = 'm';
    case FEMALE = 'f';
    case UNKNOWN = 'n';

    /**
     * 获取中文描述
     */
    public function getLabel(): string
    {
        return match ($this) {
            self::MALE => '男',
            self::FEMALE => '女',
            self::UNKNOWN => '未知',
        };
    }

    /**
     * 从字符串创建枚举实例
     */
    public static function fromString(?string $value): ?self
    {
        if ($value === null) {
            return null;
        }

        return match (strtolower($value)) {
            'm', 'male', '男' => self::MALE,
            'f', 'female', '女' => self::FEMALE,
            default => self::UNKNOWN,
        };
    }
}
