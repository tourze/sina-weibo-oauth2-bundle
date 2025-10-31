<?php

declare(strict_types=1);

namespace Tourze\SinaWeiboOAuth2Bundle\Service;

use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\CrudMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\SubMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use Knp\Menu\ItemInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;
use Tourze\SinaWeiboOAuth2Bundle\Controller\Admin\SinaWeiboOAuth2ConfigCrudController;
use Tourze\SinaWeiboOAuth2Bundle\Controller\Admin\SinaWeiboOAuth2StateCrudController;
use Tourze\SinaWeiboOAuth2Bundle\Controller\Admin\SinaWeiboOAuth2UserCrudController;

/**
 * 新浪微博OAuth2 Bundle 后台菜单服务
 *
 * 为EasyAdmin后台管理系统提供新浪微博OAuth2相关的菜单项配置
 */
final class AdminMenu implements MenuProviderInterface
{
    /**
     * 实现MenuProviderInterface的__invoke方法
     */
    public function __invoke(ItemInterface $item): void
    {
        // 为了符合MenuProviderInterface的要求，此方法用于向现有菜单项添加子项
        // 但由于我们的功能主要通过静态方法提供，此方法保持最小实现
    }

    /**
     * 获取新浪微博OAuth2模块的菜单项
     *
     * @return SubMenuItem[] 菜单项数组
     */
    public static function getMenuItems(): array
    {
        return [
            MenuItem::subMenu('新浪微博OAuth2', 'fas fa-weibo')
                ->setSubItems([
                    MenuItem::linkToCrud('OAuth2配置', 'fas fa-cog', SinaWeiboOAuth2ConfigCrudController::class),
                    MenuItem::linkToCrud('OAuth2用户', 'fas fa-users', SinaWeiboOAuth2UserCrudController::class),
                    MenuItem::linkToCrud('OAuth2状态', 'fas fa-list-alt', SinaWeiboOAuth2StateCrudController::class),
                ]),
        ];
    }

    /**
     * 获取单独的菜单项（不使用子菜单分组）
     *
     * @return CrudMenuItem[] 菜单项数组
     */
    public static function getFlatMenuItems(): array
    {
        return [
            MenuItem::linkToCrud('OAuth2配置管理', 'fas fa-cog', SinaWeiboOAuth2ConfigCrudController::class),
            MenuItem::linkToCrud('OAuth2用户管理', 'fas fa-users', SinaWeiboOAuth2UserCrudController::class),
            MenuItem::linkToCrud('OAuth2状态管理', 'fas fa-list-alt', SinaWeiboOAuth2StateCrudController::class),
        ];
    }

    /**
     * 获取新浪微博OAuth2模块的菜单项（带权限控制）
     *
     * @param bool $canManageConfig 是否可以管理配置
     * @param bool $canManageUsers 是否可以管理用户
     * @param bool $canManageStates 是否可以管理状态
     * @return SubMenuItem[] 菜单项数组
     */
    public static function getMenuItemsWithPermissions(
        bool $canManageConfig = true,
        bool $canManageUsers = true,
        bool $canManageStates = true,
    ): array {
        $items = [];

        if ($canManageConfig) {
            $items[] = MenuItem::linkToCrud('OAuth2配置', 'fas fa-cog', SinaWeiboOAuth2ConfigCrudController::class);
        }

        if ($canManageUsers) {
            $items[] = MenuItem::linkToCrud('OAuth2用户', 'fas fa-users', SinaWeiboOAuth2UserCrudController::class);
        }

        if ($canManageStates) {
            $items[] = MenuItem::linkToCrud('OAuth2状态', 'fas fa-list-alt', SinaWeiboOAuth2StateCrudController::class);
        }

        if ([] === $items) {
            return [];
        }

        return [
            MenuItem::subMenu('新浪微博OAuth2', 'fas fa-weibo')
                ->setSubItems($items),
        ];
    }

    /**
     * 获取配置管理菜单项
     */
    public static function getConfigMenuItem(): CrudMenuItem
    {
        return MenuItem::linkToCrud('OAuth2配置', 'fas fa-cog', SinaWeiboOAuth2ConfigCrudController::class);
    }

    /**
     * 获取用户管理菜单项
     */
    public static function getUserMenuItem(): CrudMenuItem
    {
        return MenuItem::linkToCrud('OAuth2用户', 'fas fa-users', SinaWeiboOAuth2UserCrudController::class);
    }

    /**
     * 获取状态管理菜单项
     */
    public static function getStateMenuItem(): CrudMenuItem
    {
        return MenuItem::linkToCrud('OAuth2状态', 'fas fa-list-alt', SinaWeiboOAuth2StateCrudController::class);
    }
}
