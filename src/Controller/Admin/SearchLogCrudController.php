<?php

declare(strict_types=1);

namespace ProductKeywordBundle\Controller\Admin;

use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use ProductKeywordBundle\Entity\SearchLog;

/**
 * 搜索日志管理控制器
 *
 * @extends AbstractCrudController<SearchLog>
 */
#[AdminCrud(
    routePath: '/product-keyword/search-log',
    routeName: 'product_keyword_search_log',
)]
final class SearchLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SearchLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('搜索日志')
            ->setEntityLabelInPlural('搜索日志列表')
            ->setPageTitle('index', '搜索日志列表')
            ->setPageTitle('new', '新建搜索日志')
            ->setPageTitle('edit', '编辑搜索日志')
            ->setPageTitle('detail', '搜索日志详情')
            ->setHelp('index', '管理用户搜索日志，用于分析搜索行为和优化搜索结果')
            ->setDefaultSort(['createTime' => 'DESC'])
            ->setSearchFields(['keyword', 'userHash', 'source', 'sessionId'])
            ->setPaginatorPageSize(30)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield TextField::new('keyword', '搜索关键词')
            ->setHelp('用户输入的搜索关键词')
            ->setRequired(true)
            ->formatValue($this->formatKeyword(...))
        ;

        yield TextField::new('userHash', '用户哈希')
            ->setHelp('用户唯一标识哈希值')
            ->setRequired(true)
            ->formatValue($this->formatUserHash(...))
            ->hideOnIndex()
        ;

        yield IntegerField::new('resultCount', '结果数量')
            ->setHelp('搜索返回的结果数量')
            ->formatValue($this->formatResultCount(...))
        ;

        yield TextField::new('source', '搜索来源')
            ->setHelp('搜索请求来源')
            ->setRequired(true)
            ->formatValue($this->formatSource(...))
        ;

        yield TextField::new('sessionId', '会话ID')
            ->setHelp('用户会话标识')
            ->setRequired(true)
            ->hideOnIndex()
            ->formatValue($this->formatSessionId(...))
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->formatValue($this->formatDateTime(...))
        ;
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('keyword', '搜索关键词'))
            ->add(TextFilter::new('userHash', '用户哈希'))
            ->add(TextFilter::new('source', '搜索来源'))
            ->add(NumericFilter::new('resultCount', '结果数量'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->orderBy('entity.createTime', 'DESC')
        ;
    }

    private function formatKeyword(mixed $value): string
    {
        if (null === $value || '' === $value) {
            return '-';
        }
        assert(is_string($value));

        return mb_strlen($value) > 50 ? mb_substr($value, 0, 50) . '...' : $value;
    }

    private function formatUserHash(mixed $value): string
    {
        if (null === $value || '' === $value) {
            return '-';
        }
        assert(is_string($value));

        return mb_substr($value, 0, 8) . '...';
    }

    private function formatResultCount(mixed $value): string
    {
        assert(is_int($value));
        if (0 === $value) {
            return '<span class="badge badge-warning">0 个</span>';
        }

        return sprintf('<span class="badge badge-success">%d 个</span>', $value);
    }

    private function formatSource(mixed $value): string
    {
        assert(is_string($value));
        $badges = [
            'web' => 'primary',
            'mobile' => 'info',
            'api' => 'success',
            'admin' => 'warning',
        ];

        $badgeClass = $badges[$value] ?? 'secondary';

        return sprintf('<span class="badge badge-%s">%s</span>', $badgeClass, $value);
    }

    private function formatSessionId(mixed $value): string
    {
        if (null === $value || '' === $value) {
            return '-';
        }
        assert(is_string($value));

        return mb_substr($value, 0, 12) . '...';
    }

    private function formatDateTime(mixed $value): string
    {
        if (null === $value) {
            return '-';
        }
        assert($value instanceof \DateTimeInterface);

        return $value->format('Y-m-d H:i:s');
    }
}
