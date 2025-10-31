<?php

namespace ProductKeywordBundle\Controller\Admin;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminAction;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminCrud;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\BooleanFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\DateTimeFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\NumericFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\TextFilter;
use ProductKeywordBundle\Entity\Keyword;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 关键词管理控制器
 *
 * @extends AbstractCrudController<Keyword>
 */
#[AdminCrud(
    routePath: '/product-keyword/keyword',
    routeName: 'product_keyword_keyword',
)]
final class KeywordCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Keyword::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('关键词')
            ->setEntityLabelInPlural('关键词列表')
            ->setPageTitle('index', '关键词列表')
            ->setPageTitle('new', '新建关键词')
            ->setPageTitle('edit', '编辑关键词')
            ->setPageTitle('detail', '关键词详情')
            ->setHelp('index', '管理商品关键词，支持分层结构、权重设置和推荐管理')
            ->setDefaultSort(['id' => 'DESC'])
            ->setSearchFields(['id', 'keyword', 'description'])
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

        yield TextField::new('keyword', '关键词')
            ->setHelp('关键词名称，必须唯一')
            ->setRequired(true)
        ;

        yield AssociationField::new('parent', '父级关键词')
            ->setFormTypeOptions([
                'class' => Keyword::class,
                'choice_label' => 'keyword',
                'placeholder' => '-- 无父级 --',
                'required' => false,
            ])
            ->formatValue($this->formatParentKeyword(...))
        ;

        yield NumberField::new('weight', '权重值')
            ->setHelp('权重值范围：0-100，默认为1.0')
            ->setNumDecimals(2)
            ->formatValue($this->formatWeight(...))
        ;

        yield ImageField::new('thumb', '缩略图')
            ->setBasePath('')
            ->setUploadDir('public/uploads/keyword')
            ->setUploadedFileNamePattern('[randomhash].[extension]')
            ->hideOnIndex()
        ;

        yield TextareaField::new('description', '描述')
            ->setMaxLength(200)
            ->hideOnIndex()
            ->formatValue($this->formatDescription(...))
        ;

        yield BooleanField::new('valid', '是否有效')
            ->renderAsSwitch()
        ;

        yield BooleanField::new('recommend', '是否推荐')
            ->renderAsSwitch()
        ;

        if (Crud::PAGE_DETAIL === $pageName || Crud::PAGE_INDEX === $pageName) {
            yield NumberField::new('productCount', '关联商品数')
                ->hideOnForm()
                ->formatValue($this->formatProductCount(...))
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
        // 启用关键词操作
        $enableAction = Action::new('enable', '启用')
            ->linkToCrudAction('enableKeyword')
            ->setCssClass('btn btn-success btn-sm')
            ->setIcon('fa fa-check')
            ->displayIf(static function (Keyword $entity): bool {
                return true !== $entity->isValid();
            })
        ;

        // 禁用关键词操作
        $disableAction = Action::new('disable', '禁用')
            ->linkToCrudAction('disableKeyword')
            ->setCssClass('btn btn-warning btn-sm')
            ->setIcon('fa fa-ban')
            ->displayIf(static function (Keyword $entity): bool {
                return true === $entity->isValid();
            })
        ;

        // 设为推荐操作
        $recommendAction = Action::new('recommend', '设为推荐')
            ->linkToCrudAction('recommendKeyword')
            ->setCssClass('btn btn-info btn-sm')
            ->setIcon('fa fa-star')
            ->displayIf(static function (Keyword $entity): bool {
                return true !== $entity->isRecommend();
            })
        ;

        // 取消推荐操作
        $unrecommendAction = Action::new('unrecommend', '取消推荐')
            ->linkToCrudAction('unrecommendKeyword')
            ->setCssClass('btn btn-secondary btn-sm')
            ->setIcon('fa fa-star-o')
            ->displayIf(static function (Keyword $entity): bool {
                return true === $entity->isRecommend();
            })
        ;

        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $enableAction)
            ->add(Crud::PAGE_INDEX, $disableAction)
            ->add(Crud::PAGE_INDEX, $recommendAction)
            ->add(Crud::PAGE_INDEX, $unrecommendAction)
            ->add(Crud::PAGE_DETAIL, $enableAction)
            ->add(Crud::PAGE_DETAIL, $disableAction)
            ->add(Crud::PAGE_DETAIL, $recommendAction)
            ->add(Crud::PAGE_DETAIL, $unrecommendAction)
            ->reorder(Crud::PAGE_INDEX, [
                Action::DETAIL,
                'enable',
                'disable',
                'recommend',
                'unrecommend',
            ])
        ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(TextFilter::new('id', 'ID'))
            ->add(TextFilter::new('keyword', '关键词'))
            ->add(EntityFilter::new('parent', '父级关键词'))
            ->add(NumericFilter::new('weight', '权重值'))
            ->add(BooleanFilter::new('valid', '是否有效'))
            ->add(BooleanFilter::new('recommend', '是否推荐'))
            ->add(DateTimeFilter::new('createTime', '创建时间'))
            ->add(TextFilter::new('description', '描述'))
        ;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        return parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters)
            ->select('entity', 'parent')
            ->leftJoin('entity.parent', 'parent')
            ->orderBy('entity.id', 'DESC')
        ;
    }

    /**
     * 启用关键词
     */
    #[AdminAction(routePath: '{entityId}/enable', routeName: 'product_keyword_enable')]
    public function enableKeyword(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof Keyword);

        $entity->setValid(true);
        $em = $this->container->get('doctrine');
        assert($em instanceof Registry);
        $manager = $em->getManager();
        assert($manager instanceof EntityManagerInterface);
        $manager->flush();

        $this->addFlash('success', sprintf('关键词 "%s" 已启用', $entity->getKeyword()));
        $referer = $context->getRequest()->headers->get('referer');

        return $this->redirect($referer ?? $this->generateUrl('admin'));
    }

    /**
     * 禁用关键词
     */
    #[AdminAction(routePath: '{entityId}/disable', routeName: 'product_keyword_disable')]
    public function disableKeyword(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof Keyword);

        $entity->setValid(false);
        $em = $this->container->get('doctrine');
        assert($em instanceof Registry);
        $manager = $em->getManager();
        assert($manager instanceof EntityManagerInterface);
        $manager->flush();

        $this->addFlash('warning', sprintf('关键词 "%s" 已禁用', $entity->getKeyword()));
        $referer = $context->getRequest()->headers->get('referer');

        return $this->redirect($referer ?? $this->generateUrl('admin'));
    }

    /**
     * 设为推荐
     */
    #[AdminAction(routePath: '{entityId}/recommend', routeName: 'product_keyword_recommend')]
    public function recommendKeyword(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof Keyword);

        $entity->setRecommend(true);
        $em = $this->container->get('doctrine');
        assert($em instanceof Registry);
        $manager = $em->getManager();
        assert($manager instanceof EntityManagerInterface);
        $manager->flush();

        $this->addFlash('info', sprintf('关键词 "%s" 已设为推荐', $entity->getKeyword()));
        $referer = $context->getRequest()->headers->get('referer');

        return $this->redirect($referer ?? $this->generateUrl('admin'));
    }

    /**
     * 取消推荐
     */
    #[AdminAction(routePath: '{entityId}/unrecommend', routeName: 'product_keyword_unrecommend')]
    public function unrecommendKeyword(AdminContext $context, Request $request): Response
    {
        $entity = $context->getEntity()->getInstance();
        assert($entity instanceof Keyword);

        $entity->setRecommend(false);
        $em = $this->container->get('doctrine');
        assert($em instanceof Registry);
        $manager = $em->getManager();
        assert($manager instanceof EntityManagerInterface);
        $manager->flush();

        $this->addFlash('secondary', sprintf('关键词 "%s" 已取消推荐', $entity->getKeyword()));
        $referer = $context->getRequest()->headers->get('referer');

        return $this->redirect($referer ?? $this->generateUrl('admin'));
    }

    private function formatParentKeyword(mixed $value, mixed $entity): string
    {
        if (null === $value) {
            return '-';
        }
        assert($value instanceof Keyword);

        return sprintf('%s', $value->getKeyword());
    }

    private function formatWeight(mixed $value): string
    {
        assert(is_numeric($value));

        return sprintf('%.2f', (float) $value);
    }

    private function formatDescription(mixed $value): string
    {
        if (null === $value || '' === $value) {
            return '-';
        }
        assert(is_string($value));

        return mb_strlen($value) > 200 ? mb_substr($value, 0, 200) . '...' : $value;
    }

    private function formatProductCount(mixed $value, mixed $entity): string
    {
        assert($entity instanceof Keyword);
        $count = $entity->getProductKeywords()->count();

        return sprintf('%d 个', $count);
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
