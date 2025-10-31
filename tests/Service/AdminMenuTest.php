<?php

declare(strict_types=1);

namespace Tourze\SinaWeiboOAuth2Bundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;
use Tourze\SinaWeiboOAuth2Bundle\Service\AdminMenu;

/**
 * @covers \Tourze\SinaWeiboOAuth2Bundle\Service\AdminMenu
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    protected function onSetUp(): void
    {
        // 设置测试环境
    }

    public function testGetMenuItemsReturnsArray(): void
    {
        $menuItems = AdminMenu::getMenuItems();
        $this->assertIsArray($menuItems);
        $this->assertCount(1, $menuItems);
    }

    public function testGetFlatMenuItemsReturnsArray(): void
    {
        $menuItems = AdminMenu::getFlatMenuItems();
        $this->assertIsArray($menuItems);
        $this->assertCount(3, $menuItems);
    }

    public function testGetMenuItemsWithPermissionsAllEnabled(): void
    {
        $menuItems = AdminMenu::getMenuItemsWithPermissions(true, true, true);
        $this->assertIsArray($menuItems);
        $this->assertCount(1, $menuItems);
    }

    public function testGetMenuItemsWithPermissionsOnlyConfig(): void
    {
        $menuItems = AdminMenu::getMenuItemsWithPermissions(true, false, false);
        $this->assertIsArray($menuItems);
        $this->assertCount(1, $menuItems);
    }

    public function testGetMenuItemsWithPermissionsOnlyUsers(): void
    {
        $menuItems = AdminMenu::getMenuItemsWithPermissions(false, true, false);
        $this->assertIsArray($menuItems);
        $this->assertCount(1, $menuItems);
    }

    public function testGetMenuItemsWithPermissionsOnlyStates(): void
    {
        $menuItems = AdminMenu::getMenuItemsWithPermissions(false, false, true);
        $this->assertIsArray($menuItems);
        $this->assertCount(1, $menuItems);
    }

    public function testGetMenuItemsWithPermissionsAllDisabled(): void
    {
        $menuItems = AdminMenu::getMenuItemsWithPermissions(false, false, false);
        $this->assertIsArray($menuItems);
        $this->assertEmpty($menuItems);
    }

    public function testGetConfigMenuItemExists(): void
    {
        $menuItem = AdminMenu::getConfigMenuItem();
        $this->assertNotNull($menuItem);
    }

    public function testGetUserMenuItemExists(): void
    {
        $menuItem = AdminMenu::getUserMenuItem();
        $this->assertNotNull($menuItem);
    }

    public function testGetStateMenuItemExists(): void
    {
        $menuItem = AdminMenu::getStateMenuItem();
        $this->assertNotNull($menuItem);
    }

    public function testMenuItemsAreIndependent(): void
    {
        $menuItems1 = AdminMenu::getMenuItems();
        $menuItems2 = AdminMenu::getMenuItems();

        $this->assertNotSame($menuItems1, $menuItems2);
        $this->assertEquals($menuItems1, $menuItems2);
    }

    public function testFlatMenuItemsAreIndependent(): void
    {
        $menuItems1 = AdminMenu::getFlatMenuItems();
        $menuItems2 = AdminMenu::getFlatMenuItems();

        $this->assertNotSame($menuItems1, $menuItems2);
        $this->assertEquals($menuItems1, $menuItems2);
    }

    public function testSingleMenuItemsAreIndependent(): void
    {
        $configItem1 = AdminMenu::getConfigMenuItem();
        $configItem2 = AdminMenu::getConfigMenuItem();

        $this->assertNotSame($configItem1, $configItem2);
        $this->assertEquals($configItem1, $configItem2);
    }
}
