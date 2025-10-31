<?php

namespace ProductKeywordBundle\Service;

use Knp\Menu\ItemInterface;
use ProductKeywordBundle\Entity\Keyword;
use ProductKeywordBundle\Entity\ProductKeyword;
use ProductKeywordBundle\Entity\SearchLog;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\EasyAdminMenuBundle\Service\MenuProviderInterface;

/**
 * 商品关键词管理菜单服务
 */
readonly class AdminMenu implements MenuProviderInterface
{
    public function __construct(
        private LinkGeneratorInterface $linkGenerator,
    ) {
    }

    public function __invoke(ItemInterface $item): void
    {
        // 确保商品管理菜单存在
        if (null === $item->getChild('商品管理')) {
            $item->addChild('商品管理');
        }

        $productMenu = $item->getChild('商品管理');
        if (null === $productMenu) {
            return;
        }

        // 创建关键词管理子菜单
        if (null === $productMenu->getChild('关键词管理')) {
            $productMenu->addChild('关键词管理')
                ->setAttribute('icon', 'fas fa-tags')
            ;
        }

        $keywordMenu = $productMenu->getChild('关键词管理');
        if (null === $keywordMenu) {
            return;
        }

        // 关键词菜单
        $keywordMenu->addChild('关键词列表')
            ->setUri($this->linkGenerator->getCurdListPage(Keyword::class))
            ->setAttribute('icon', 'fas fa-tag')
        ;

        // 商品关键词关联菜单
        $keywordMenu->addChild('商品关键词')
            ->setUri($this->linkGenerator->getCurdListPage(ProductKeyword::class))
            ->setAttribute('icon', 'fas fa-link')
        ;

        // 搜索日志菜单
        $keywordMenu->addChild('搜索日志')
            ->setUri($this->linkGenerator->getCurdListPage(SearchLog::class))
            ->setAttribute('icon', 'fas fa-search')
        ;
    }
}
