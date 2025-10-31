<?php

declare(strict_types=1);

namespace Tourze\SinaWeiboOAuth2Bundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2User;

/**
 * 新浪微博OAuth2用户管理
 *
 * 提供新浪微博OAuth2授权用户的完整管理功能，包括用户信息查看、
 * 令牌状态管理和关联配置管理等
 *
 * @extends AbstractCrudController<SinaWeiboOAuth2User>
 */
#[AdminCrud(
    routePath: '/sina-weibo-oauth2/user',
    routeName: 'sina_weibo_oauth2_user'
)]
final class SinaWeiboOAuth2UserCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SinaWeiboOAuth2User::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('微博OAuth2用户')
            ->setEntityLabelInPlural('微博OAuth2用户列表')
            ->setPageTitle('index', '微博OAuth2用户列表')
            ->setPageTitle('new', '创建微博OAuth2用户')
            ->setPageTitle('edit', '编辑微博OAuth2用户')
            ->setPageTitle('detail', '微博OAuth2用户详情')
            ->setHelp('index', '管理通过新浪微博OAuth2授权的用户信息，包括令牌管理和用户资料')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'uid', 'nickname', 'location'])
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        // 基本字段
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield TextField::new('uid', '微博UID')
            ->setRequired(true)
            ->setHelp('新浪微博用户的唯一标识符')
            ->setFormTypeOption('attr', ['readonly' => Crud::PAGE_EDIT === $pageName])
        ;

        yield AssociationField::new('config', 'OAuth2配置')
            ->setRequired(true)
            ->autocomplete()
            ->setHelp('关联的新浪微博OAuth2应用配置')
        ;

        // 用户信息字段
        yield TextField::new('nickname', '昵称')
            ->setHelp('微博用户的昵称')
        ;

        yield ImageField::new('avatar', '头像')
            ->setBasePath('')
            ->onlyOnDetail()
            ->setHelp('用户头像图片预览')
        ;

        yield UrlField::new('avatar', '头像URL')
            ->hideOnDetail()
            ->setHelp('用户头像的URL地址')
        ;

        yield TextField::new('gender', '性别')
            ->setHelp('用户性别信息')
        ;

        yield TextField::new('location', '地理位置')
            ->setHelp('用户所在地理位置')
        ;

        yield TextareaField::new('description', '个人描述')
            ->setHelp('用户的个人简介描述')
            ->hideOnIndex()
        ;

        // 令牌相关字段 - 敏感信息处理
        yield TextField::new('accessToken', '访问令牌')
            ->hideOnIndex()
            ->setFormTypeOption('attr', ['readonly' => true])
            ->setHelp('OAuth2访问令牌（敏感信息，只读显示）')
            ->formatValue(function ($value) {
                return $value ? substr($value, 0, 10) . '...' : '';
            })
        ;

        yield TextField::new('refreshToken', '刷新令牌')
            ->hideOnIndex()
            ->hideOnForm()
            ->onlyOnDetail()
            ->setHelp('OAuth2刷新令牌（敏感信息）')
            ->formatValue(function ($value) {
                return $value ? substr($value, 0, 10) . '...' : '无';
            })
        ;

        yield DateTimeField::new('tokenExpireTime', '令牌过期时间')
            ->setHelp('访问令牌的过期时间')
            ->formatValue(function ($value) use ($pageName) {
                if (!$value instanceof \DateTimeInterface) {
                    return '';
                }

                $now = new \DateTimeImmutable();
                $isExpired = $value <= $now;

                if (Crud::PAGE_INDEX === $pageName) {
                    $status = $isExpired ? ' [已过期]' : ' [有效]';

                    return $value->format('Y-m-d H:i:s') . $status;
                }

                return $value->format('Y-m-d H:i:s');
            })
        ;

        // JSON数据字段
        yield CodeEditorField::new('rawData', '原始API数据')
            ->setLanguage('javascript')
            ->hideOnIndex()
            ->onlyOnDetail()
            ->setHelp('微博API返回的原始JSON数据')
            ->formatValue(function ($value) {
                return $value ? json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : '';
            })
        ;

        // 时间戳字段
        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->setHelp('记录创建时间')
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->setHelp('记录最后更新时间')
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        // 添加详情查看动作
        $actions->add(Crud::PAGE_INDEX, Action::DETAIL);

        // 移除新建动作，因为用户数据应通过OAuth2流程创建
        $actions->disable(Action::NEW);

        return $actions;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(EntityFilter::new('config', 'OAuth2配置'))
            ->add(TextFilter::new('uid', '微博UID'))
            ->add(TextFilter::new('nickname', '昵称'))
            ->add(TextFilter::new('gender', '性别'))
            ->add(TextFilter::new('location', '地理位置'))
            ->add(DateTimeFilter::new('tokenExpireTime', '令牌过期时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(DateTimeFilter::new('updateTime', '更新时间'))
        ;
    }
}
