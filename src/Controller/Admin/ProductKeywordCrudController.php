<?php

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
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use ProductKeywordBundle\Entity\Keyword;
use ProductKeywordBundle\Entity\ProductKeyword;

/**
 * 商品关键词关联管理控制器
 *
 * @extends AbstractCrudController<ProductKeyword>
 */
#[AdminCrud(
    routePath: '/product-keyword/relation',
    routeName: 'product_keyword_relation',
)]
final class ProductKeywordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductKeyword::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('商品关键词关联')
            ->setEntityLabelInPlural('商品关键词关联列表')
            ->setPageTitle('index', '商品关键词关联列表')
            ->setPageTitle('new', '新建商品关键词关联')
            ->setPageTitle('edit', '编辑商品关键词关联')
            ->setPageTitle('detail', '商品关键词关联详情')
            ->setHelp('index', '管理商品与关键词的关联关系，设置权重和来源')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'spuId'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined()
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID')
            ->setMaxLength(9999)
            ->hideOnForm()
        ;

        yield TextField::new('spuId', '商品SPU')
            ->setHelp('商品的SPU ID')
            ->setRequired(true)
            ->formatValue($this->formatSpuId(...))
        ;

        yield AssociationField::new('keyword', '关键词')
            ->setFormTypeOptions([
                'class' => Keyword::class,
                'choice_label' => 'keyword',
                'placeholder' => '-- 选择关键词 --',
                'required' => true,
            ])
            ->formatValue($this->formatKeyword(...))
        ;

        yield NumberField::new('weight', '权重值')
            ->setHelp('权重值范围：0-10，默认为1.0')
            ->setNumDecimals(2)
            ->formatValue($this->formatWeight(...))
        ;

        yield ChoiceField::new('source', '来源')
            ->setChoices([
                '手动添加' => 'manual',
                '自动生成' => 'auto',
                '批量导入' => 'import',
            ])
            ->renderAsBadges([
                'manual' => 'primary',
                'auto' => 'info',
                'import' => 'success',
            ])
        ;

        if (Crud::PAGE_DETAIL === $pageName || Crud::PAGE_INDEX === $pageName) {
            yield NumberField::new('keywordWeight', '关键词权重')
                ->hideOnForm()
                ->formatValue($this->formatKeywordWeight(...))
            ;

            yield TextField::new('keywordParent', '关键词父级')
                ->hideOnForm()
                ->hideOnIndex()
                ->formatValue($this->formatKeywordParent(...))
            ;
        }

        yield TextField::new('createdBy', '创建人')
            ->hideOnForm()
            ->hideOnIndex()
            ->formatValue($this->formatStringUser(...))
        ;

        yield TextField::new('updatedBy', '更新人')
            ->hideOnForm()
            ->hideOnIndex()
            ->formatValue($this->formatStringUser(...))
        ;

        yield DateTimeField::new('createTime', '创建时间')
            ->hideOnForm()
            ->formatValue($this->formatDateTime(...))
        ;

        yield DateTimeField::new('updateTime', '更新时间')
            ->hideOnForm()
            ->hideOnIndex()
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
        // 构建来源选项
        $sourceChoices = [
            '手动添加' => 'manual',
            '自动生成' => 'auto',
            '批量导入' => 'import',
        ];

        return $filters
            ->add(TextFilter::new('id', 'ID'))
            ->add(TextFilter::new('spuId', '商品SPU'))
            ->add(EntityFilter::new('keyword', '关键词'))
            ->add(NumericFilter::new('weight', '权重值'))
            ->add(ChoiceFilter::new('source', '来源')->setChoices($sourceChoices))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->select('entity', 'keyword')
            ->leftJoin('entity.keyword', 'keyword')
            ->orderBy('entity.id', 'DESC')
        ;
    }

    private function formatSpuId(mixed $value): string
    {
        assert(is_scalar($value));

        return sprintf('#%s', (string) $value);
    }

    private function formatKeyword(mixed $value, mixed $entity): string
    {
        if (null === $value) {
            return '-';
        }
        assert($value instanceof Keyword);

        $text = $value->getKeyword() ?? '';
        if (true !== $value->isValid()) {
            $text .= ' (已禁用)';
        }
        if (true === $value->isRecommend()) {
            $text .= ' ⭐';
        }

        return $text;
    }

    private function formatWeight(mixed $value): string
    {
        assert(is_numeric($value));

        return sprintf('%.2f', (float) $value);
    }

    private function formatKeywordWeight(mixed $value, mixed $entity): string
    {
        assert($entity instanceof ProductKeyword);
        $keyword = $entity->getKeyword();

        return sprintf('%.2f', $keyword->getWeight());
    }

    private function formatKeywordParent(mixed $value, mixed $entity): string
    {
        assert($entity instanceof ProductKeyword);
        $keyword = $entity->getKeyword();
        $parent = $keyword->getParent();

        if (null === $parent) {
            return '-';
        }

        return $parent->getKeyword() ?? '-';
    }

    private function formatStringUser(mixed $value): string
    {
        if (null === $value || '' === $value) {
            return '-';
        }
        assert(is_string($value));

        return $value;
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
