<?php

declare(strict_types=1);

namespace Tourze\SinaWeiboOAuth2Bundle\Controller\Admin;

use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Field\FieldInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use Tourze\SinaWeiboOAuth2Bundle\Entity\SinaWeiboOAuth2State;

/**
 * 新浪微博OAuth2状态管理控制器
 *
 * 用于管理OAuth2授权流程中的状态码，包括状态生成、过期检查、使用状态跟踪等功能。
 * State字段在列表中显示但在表单中为只读，确保数据完整性。
 *
 * @extends AbstractCrudController<SinaWeiboOAuth2State>
 */
#[AdminCrud(
    routePath: '/sina-weibo-oauth2/state',
    routeName: 'sina_weibo_oauth2_state'
)]
final class SinaWeiboOAuth2StateCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SinaWeiboOAuth2State::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('OAuth2状态')
            ->setEntityLabelInPlural('OAuth2状态管理')
            ->setPageTitle('index', 'OAuth2状态列表')
            ->setPageTitle('new', '创建OAuth2状态')
            ->setPageTitle('edit', 'OAuth2状态编辑')
            ->setPageTitle('detail', 'OAuth2状态详情')
            ->setHelp('index', '管理新浪微博OAuth2授权流程中的状态码，用于防止CSRF攻击和跟踪授权会话')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['state', 'sessionId'])
            ->setPaginatorPageSize(25)
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

        yield FormField::addPanel('状态信息')
            ->setIcon('fa fa-key')
        ;

        yield TextField::new('state', 'OAuth2状态码')
            ->setMaxLength(255)
            ->setRequired(true)
            ->setHelp('用于OAuth2授权流程的唯一状态标识符，防止CSRF攻击')
            ->setFormTypeOption('disabled', Crud::PAGE_NEW !== $pageName)
        ;

        yield AssociationField::new('config', 'OAuth2配置')
            ->setRequired(true)
            ->autocomplete()
            ->setHelp('关联的新浪微博OAuth2应用配置')
        ;

        yield FormField::addPanel('会话信息')
            ->setIcon('fa fa-clock')
        ;

        yield DateTimeField::new('expireTime', '过期时间')
            ->setRequired(true)
            ->setHelp('状态码的过期时间，过期后不能使用')
            ->formatValue(function ($value) {
                if (!$value instanceof \DateTimeInterface) {
                    return '';
                }

                return $value->format('Y-m-d H:i:s');
            })
        ;

        yield TextField::new('sessionId', '会话ID')
            ->setMaxLength(255)
            ->setHelp('关联的用户会话标识符')
            ->hideOnIndex()
        ;

        yield FormField::addPanel('状态标记')
            ->setIcon('fa fa-check-circle')
        ;

        yield BooleanField::new('used', '已使用')
            ->setHelp('标记此状态码是否已被使用')
            ->renderAsSwitch(false)
        ;

        yield FormField::addPanel('系统信息')
            ->setIcon('fa fa-info-circle')
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->formatValue(function ($value) {
                if (!$value instanceof \DateTimeInterface) {
                    return '';
                }

                return $value->format('Y-m-d H:i:s');
            })
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->hideOnIndex()
            ->formatValue(function ($value) {
                if (!$value instanceof \DateTimeInterface) {
                    return '';
                }

                return $value->format('Y-m-d H:i:s');
            })
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('state', 'OAuth2状态码'))
            ->add(EntityFilter::new('config', 'OAuth2配置'))
            ->add(BooleanFilter::new('used', '已使用'))
            ->add(DateTimeFilter::new('expireTime', '过期时间'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(TextFilter::new('sessionId', '会话ID'))
        ;
    }
}
