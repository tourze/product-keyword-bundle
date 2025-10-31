<?php

namespace ProductKeywordBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ProductKeywordBundle\Entity\Keyword;
use ProductKeywordBundle\Repository\KeywordRepository;
use ProductKeywordBundle\Service\KeywordSearchService;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(KeywordSearchService::class)]
#[RunTestsInSeparateProcesses]
final class KeywordSearchServiceTest extends AbstractIntegrationTestCase
{
    private KeywordSearchService $service;

    private KeywordRepository $keywordRepository;

    protected function onSetUp(): void
    {
        $service = self::getContainer()->get(KeywordSearchService::class);
        $this->assertInstanceOf(KeywordSearchService::class, $service);
        $this->service = $service;

        $keywordRepository = self::getContainer()->get(KeywordRepository::class);
        $this->assertInstanceOf(KeywordRepository::class, $keywordRepository);
        $this->keywordRepository = $keywordRepository;
    }

    public function testFindProductsByKeywordWithValidKeyword(): void
    {
        // 创建并保存一个有效的关键词实体
        $keywordEntity = new Keyword();
        $keywordEntity->setKeyword('integration-test');
        $keywordEntity->setValid(true);
        $keywordEntity->setWeight(1.0);

        $this->keywordRepository->save($keywordEntity, true);

        $result = $this->service->findProductsByKeyword('integration-test');

        // 应该返回空数组，因为没有相关的商品关键词关联
        $this->assertEquals([], $result);
    }

    public function testFindProductsByKeywordWithInvalidKeyword(): void
    {
        // 创建并保存一个无效的关键词实体
        $keywordEntity = new Keyword();
        $keywordEntity->setKeyword('invalid-test');
        $keywordEntity->setValid(false);
        $keywordEntity->setWeight(1.0);

        $this->keywordRepository->save($keywordEntity, true);

        $result = $this->service->findProductsByKeyword('invalid-test');

        // 应该返回空数组，因为关键词无效
        $this->assertEquals([], $result);
    }

    public function testFindProductsByKeywordsWithEmptyArray(): void
    {
        $result = $this->service->findProductsByKeywords([]);

        // 应该返回空数组
        $this->assertEquals([], $result);
    }

    public function testFindProductsByKeywordsWithValidKeywords(): void
    {
        // 创建并保存有效关键词
        $keyword1 = new Keyword();
        $keyword1->setKeyword('test-1');
        $keyword1->setValid(true);
        $keyword1->setWeight(1.0);

        $keyword2 = new Keyword();
        $keyword2->setKeyword('test-2');
        $keyword2->setValid(true);
        $keyword2->setWeight(1.0);

        $this->keywordRepository->save($keyword1, true);
        $this->keywordRepository->save($keyword2, true);

        $result = $this->service->findProductsByKeywords(['test-1', 'test-2']);

        // 应该返回空数组，因为没有相关的商品关键词关联
        $this->assertEquals([], $result);
    }

    public function testFindProductsByKeywordsWithNoValidKeywords(): void
    {
        $result = $this->service->findProductsByKeywords(['non-existent']);

        // 应该返回空数组
        $this->assertEquals([], $result);
    }
}
