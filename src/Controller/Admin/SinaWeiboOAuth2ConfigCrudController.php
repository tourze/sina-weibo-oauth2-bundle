<?php

declare(strict_types=1);

namespace Tourze\SinaWeiboOAuth2Bundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2Config;

/**
 * 新浪微博OAuth2配置管理控制器
 *
 * @extends AbstractCrudController<SinaWeiboOAuth2Config>
 */
#[AdminCrud(
    routePath: '/sina-weibo-oauth2/config',
    routeName: 'sina_weibo_oauth2_config'
)]
final class SinaWeiboOAuth2ConfigCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SinaWeiboOAuth2Config::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('微博OAuth2配置')
            ->setEntityLabelInPlural('微博OAuth2配置管理')
            ->setPageTitle('index', '微博OAuth2配置列表')
            ->setPageTitle('new', '创建微博OAuth2配置')
            ->setPageTitle('edit', '编辑微博OAuth2配置')
            ->setPageTitle('detail', '微博OAuth2配置详情')
            ->setHelp('index', '管理新浪微博OAuth2授权配置，用于第三方登录和API调用')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['appId'])
            ->setPaginatorPageSize(20)
        ;
    }

    /**
     * @return iterable<FieldInterface>
     */
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield TextField::new('appId', '应用ID')
            ->setMaxLength(255)
            ->setRequired(true)
            ->setHelp('新浪微博开放平台分配的应用唯一标识符')
        ;

        yield TextField::new('appSecret', '应用密钥')
            ->setMaxLength(500)
            ->setRequired(true)
            ->hideOnIndex()
            ->setFormType(PasswordType::class)
            ->setHelp('新浪微博开放平台分配的应用密钥，请妥善保管')
            ->formatValue(static function (mixed $value): string {
                return is_string($value) && '' !== $value ? str_repeat('*', min(strlen($value), 20)) : '';
            })
        ;

        yield TextareaField::new('scope', '授权范围')
            ->setMaxLength(500)
            ->hideOnIndex()
            ->setRequired(false)
            ->setHelp('OAuth2授权时请求的权限范围，多个用逗号分隔。留空表示使用默认范围')
        ;

        yield BooleanField::new('valid', '启用状态')
            ->setHelp('是否启用此配置。只有启用的配置才会被用于OAuth2授权')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->formatValue(static function (mixed $value): string {
                return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : '';
            })
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->hideOnIndex()
            ->formatValue(static function (mixed $value): string {
                return $value instanceof \DateTimeInterface ? $value->format('Y-m-d H:i:s') : '';
            })
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('appId', '应用ID'))
            ->add(BooleanFilter::new('valid', '启用状态'))
        ;
    }
}
