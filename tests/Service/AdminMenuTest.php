<?php

namespace ProductKeywordBundle\Tests\Service;

use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ProductKeywordBundle\Entity\Keyword;
use ProductKeywordBundle\Entity\ProductKeyword;
use ProductKeywordBundle\Entity\SearchLog;
use ProductKeywordBundle\Service\AdminMenu;
use Tourze\EasyAdminMenuBundle\Service\LinkGeneratorInterface;
use Tourze\PHPUnitSymfonyWebTest\AbstractEasyAdminMenuTestCase;

/**
 * AdminMenu 测试
 * @internal
 */
#[CoversClass(AdminMenu::class)]
#[RunTestsInSeparateProcesses]
final class AdminMenuTest extends AbstractEasyAdminMenuTestCase
{
    private LinkGeneratorInterface $linkGenerator;

    private AdminMenu $adminMenu;

    public function testInvokeCreatesProductManagementMenuIfNotExists(): void
    {
        $rootMenu = $this->createMock(ItemInterface::class);

        $rootMenu->expects($this->exactly(2))
            ->method('getChild')
            ->with('商品管理')
            ->willReturn(null)
        ;

        $rootMenu->expects($this->once())
            ->method('addChild')
            ->with('商品管理')
        ;

        $this->adminMenu->__invoke($rootMenu);
    }

    public function testInvokeCreatesKeywordManagementSubMenu(): void
    {
        $rootMenu = $this->createMock(ItemInterface::class);
        $productMenu = $this->createMock(ItemInterface::class);
        $keywordMenu = $this->createMock(ItemInterface::class);
        $keywordListMenu = $this->createMock(ItemInterface::class);
        $productKeywordMenu = $this->createMock(ItemInterface::class);
        $searchLogMenu = $this->createMock(ItemInterface::class);

        $rootMenu->expects($this->exactly(2))
            ->method('getChild')
            ->with('商品管理')
            ->willReturn($productMenu)
        ;

        $productMenu->expects($this->exactly(2))
            ->method('getChild')
            ->with('关键词管理')
            ->willReturnOnConsecutiveCalls(null, $keywordMenu)
        ;

        $productMenu->expects($this->once())
            ->method('addChild')
            ->with('关键词管理')
            ->willReturn($keywordMenu)
        ;

        $keywordMenu->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-tags')
            ->willReturn($keywordMenu) // 链式调用需要返回自身
        ;

        // @phpstan-ignore-next-line method.notFound, method.nonObject
        $this->linkGenerator->expects($this->exactly(3))
            ->method('getCurdListPage')
            ->willReturnMap([
                [Keyword::class, 'http://localhost/admin?crudAction=index&crudControllerFqcn=ProductKeywordBundle%5CEntity%5CKeyword'],
                [ProductKeyword::class, 'http://localhost/admin?crudAction=index&crudControllerFqcn=ProductKeywordBundle%5CEntity%5CProductKeyword'],
                [SearchLog::class, 'http://localhost/admin?crudAction=index&crudControllerFqcn=ProductKeywordBundle%5CEntity%5CSearchLog'],
            ])
        ;

        $keywordMenu->expects($this->exactly(3))
            ->method('addChild')
            ->willReturnCallback(function ($name) use ($keywordListMenu, $productKeywordMenu, $searchLogMenu) {
                if ('关键词列表' === $name) {
                    return $keywordListMenu;
                }
                if ('商品关键词' === $name) {
                    return $productKeywordMenu;
                }
                if ('搜索日志' === $name) {
                    return $searchLogMenu;
                }

                return null;
            })
        ;

        $keywordListMenu->expects($this->once())
            ->method('setUri')
            ->with('http://localhost/admin?crudAction=index&crudControllerFqcn=ProductKeywordBundle%5CEntity%5CKeyword')
            ->willReturn($keywordListMenu)
        ;

        $keywordListMenu->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-tag')
            ->willReturn($keywordListMenu)
        ;

        $productKeywordMenu->expects($this->once())
            ->method('setUri')
            ->with('http://localhost/admin?crudAction=index&crudControllerFqcn=ProductKeywordBundle%5CEntity%5CProductKeyword')
            ->willReturn($productKeywordMenu)
        ;

        $productKeywordMenu->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-link')
            ->willReturn($productKeywordMenu)
        ;

        $searchLogMenu->expects($this->once())
            ->method('setUri')
            ->with('http://localhost/admin?crudAction=index&crudControllerFqcn=ProductKeywordBundle%5CEntity%5CSearchLog')
            ->willReturn($searchLogMenu)
        ;

        $searchLogMenu->expects($this->once())
            ->method('setAttribute')
            ->with('icon', 'fas fa-search')
            ->willReturn($searchLogMenu)
        ;

        $this->adminMenu->__invoke($rootMenu);
    }

    public function testInvokeHandlesExistingProductMenu(): void
    {
        $rootMenu = $this->createMock(ItemInterface::class);
        $productMenu = $this->createMock(ItemInterface::class);
        $keywordMenu = $this->createMock(ItemInterface::class);

        $rootMenu->expects($this->exactly(2))
            ->method('getChild')
            ->with('商品管理')
            ->willReturn($productMenu)
        ;

        $productMenu->expects($this->exactly(2))
            ->method('getChild')
            ->with('关键词管理')
            ->willReturn($keywordMenu)
        ;

        // @phpstan-ignore-next-line method.notFound, method.nonObject
        $this->linkGenerator->expects($this->exactly(3))
            ->method('getCurdListPage')
            ->willReturnMap([
                [Keyword::class, '/admin/keyword'],
                [ProductKeyword::class, '/admin/product-keyword'],
                [SearchLog::class, '/admin/search-log'],
            ])
        ;

        $subMenuItem1 = $this->createMock(ItemInterface::class);
        $subMenuItem2 = $this->createMock(ItemInterface::class);
        $subMenuItem3 = $this->createMock(ItemInterface::class);

        $keywordMenu->expects($this->exactly(3))
            ->method('addChild')
            ->willReturnOnConsecutiveCalls($subMenuItem1, $subMenuItem2, $subMenuItem3)
        ;

        $subMenuItem1->expects($this->once())
            ->method('setUri')
            ->willReturn($subMenuItem1)
        ;
        $subMenuItem1->expects($this->once())
            ->method('setAttribute')
            ->willReturn($subMenuItem1)
        ;

        $subMenuItem2->expects($this->once())
            ->method('setUri')
            ->willReturn($subMenuItem2)
        ;
        $subMenuItem2->expects($this->once())
            ->method('setAttribute')
            ->willReturn($subMenuItem2)
        ;

        $subMenuItem3->expects($this->once())
            ->method('setUri')
            ->willReturn($subMenuItem3)
        ;
        $subMenuItem3->expects($this->once())
            ->method('setAttribute')
            ->willReturn($subMenuItem3)
        ;

        $this->adminMenu->__invoke($rootMenu);
    }

    public function testInvokeReturnsEarlyWhenProductMenuIsNull(): void
    {
        $rootMenu = $this->createMock(ItemInterface::class);

        $rootMenu->expects($this->exactly(2))
            ->method('getChild')
            ->with('商品管理')
            ->willReturn(null)
        ;

        $rootMenu->expects($this->once())
            ->method('addChild')
            ->with('商品管理')
        ;

        // @phpstan-ignore-next-line method.notFound
        $this->linkGenerator->expects($this->never())
            ->method('getCurdListPage')
        ;

        $this->adminMenu->__invoke($rootMenu);
    }

    public function testInvokeReturnsEarlyWhenKeywordMenuIsNull(): void
    {
        $rootMenu = $this->createMock(ItemInterface::class);
        $productMenu = $this->createMock(ItemInterface::class);

        $rootMenu->expects($this->exactly(2))
            ->method('getChild')
            ->with('商品管理')
            ->willReturn($productMenu)
        ;

        $productMenu->expects($this->exactly(2))
            ->method('getChild')
            ->with('关键词管理')
            ->willReturn(null)
        ;

        $productMenu->expects($this->once())
            ->method('addChild')
            ->with('关键词管理')
        ;

        // @phpstan-ignore-next-line method.notFound
        $this->linkGenerator->expects($this->never())
            ->method('getCurdListPage')
        ;

        $this->adminMenu->__invoke($rootMenu);
    }

    protected function onSetUp(): void
    {
        $this->linkGenerator = $this->createMock(LinkGeneratorInterface::class);
        self::getContainer()->set(LinkGeneratorInterface::class, $this->linkGenerator);
        $adminMenu = self::getContainer()->get(AdminMenu::class);
        $this->assertInstanceOf(AdminMenu::class, $adminMenu);
        $this->adminMenu = $adminMenu;
    }
}
