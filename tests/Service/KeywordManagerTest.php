<?php

namespace ProductKeywordBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ProductKeywordBundle\DTO\KeywordDTO;
use ProductKeywordBundle\DTO\KeywordSearchCriteria;
use ProductKeywordBundle\Entity\Keyword;
use ProductKeywordBundle\Entity\ProductKeyword;
use ProductKeywordBundle\Exception\DuplicateKeywordException;
use ProductKeywordBundle\Exception\KeywordNotFoundException;
use ProductKeywordBundle\Repository\KeywordRepository;
use ProductKeywordBundle\Repository\ProductKeywordRepository;
use ProductKeywordBundle\Service\KeywordManager;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(KeywordManager::class)]
#[RunTestsInSeparateProcesses]
final class KeywordManagerTest extends AbstractIntegrationTestCase
{
    private KeywordRepository $keywordRepository;

    private ProductKeywordRepository $productKeywordRepository;

    private KeywordManager $keywordManager;

    protected function onSetUp(): void
    {
        $keywordManager = self::getContainer()->get(KeywordManager::class);
        $this->assertInstanceOf(KeywordManager::class, $keywordManager);
        $this->keywordManager = $keywordManager;

        $keywordRepository = self::getContainer()->get(KeywordRepository::class);
        $this->assertInstanceOf(KeywordRepository::class, $keywordRepository);
        $this->keywordRepository = $keywordRepository;

        $productKeywordRepository = self::getContainer()->get(ProductKeywordRepository::class);
        $this->assertInstanceOf(ProductKeywordRepository::class, $productKeywordRepository);
        $this->productKeywordRepository = $productKeywordRepository;
    }

    /**
     * 从可迭代对象中获取第一个元素的辅助方法
     * @param iterable<Keyword> $iterable
     */
    private function getFirstFromIterable(iterable $iterable): ?Keyword
    {
        foreach ($iterable as $item) {
            return $item;
        }

        return null;
    }

    public function testCreateKeywordSuccessfully(): void
    {
        $dto = new KeywordDTO(
            keyword: 'test-integration-keyword',
            weight: 1.0,
            parentId: null,
            valid: true,
            recommend: true,
            description: 'Test description'
        );

        $result = $this->keywordManager->create($dto);

        $this->assertInstanceOf(Keyword::class, $result);
        $this->assertEquals('test-integration-keyword', $result->getKeyword());
        $this->assertEquals(1.0, $result->getWeight());
        $this->assertTrue($result->isValid());
        $this->assertTrue($result->isRecommend());
        $this->assertEquals('Test description', $result->getDescription());

        // 验证关键词已保存到数据库
        $savedKeyword = $this->keywordRepository->findByKeyword('test-integration-keyword');
        $this->assertNotNull($savedKeyword);
        $this->assertEquals($result->getId(), $savedKeyword->getId());
    }

    public function testCreateKeywordThrowsDuplicateException(): void
    {
        // 先创建一个关键词
        $keyword = new Keyword();
        $keyword->setKeyword('duplicate-test');
        $keyword->setWeight(1.0);
        $keyword->setValid(true);

        $this->keywordRepository->save($keyword, true);

        // 尝试创建重复的关键词
        $dto = new KeywordDTO(
            keyword: 'duplicate-test',
            weight: 2.0,
            parentId: null,
            valid: true,
            recommend: false,
            description: 'Duplicate test'
        );

        $this->expectException(DuplicateKeywordException::class);
        $this->keywordManager->create($dto);
    }

    public function testFindKeyword(): void
    {
        // 创建测试数据
        $keyword = new Keyword();
        $keyword->setKeyword('find-test');
        $keyword->setWeight(1.5);
        $keyword->setValid(true);

        $this->keywordRepository->save($keyword, true);

        $keywordId = $keyword->getId();
        $this->assertNotNull($keywordId, 'Keyword ID should not be null after save');
        $result = $this->keywordManager->find($keywordId);

        $this->assertNotNull($result);
        $this->assertEquals('find-test', $result->getKeyword());
        $this->assertEquals(1.5, $result->getWeight());
    }

    public function testDeleteKeywordSuccessfully(): void
    {
        // 创建测试数据
        $keyword = new Keyword();
        $keyword->setKeyword('delete-test');
        $keyword->setWeight(1.0);
        $keyword->setValid(true);

        $this->keywordRepository->save($keyword, true);
        $keywordId = $keyword->getId();
        $this->assertNotNull($keywordId, 'Keyword ID should not be null after save');
        $this->keywordManager->delete($keywordId);

        // 验证关键词已被删除
        $deletedKeyword = $this->keywordRepository->find($keywordId);
        $this->assertNull($deletedKeyword);
    }

    public function testDeleteNonExistentKeyword(): void
    {
        $this->expectException(KeywordNotFoundException::class);
        $this->keywordManager->delete('999999'); // 不存在的ID
    }

    public function testAttachToProductCreatesNew(): void
    {
        // 创建测试关键词
        $keyword = new Keyword();
        $keyword->setKeyword('attach-test');
        $keyword->setWeight(1.0);
        $keyword->setValid(true);

        $this->keywordRepository->save($keyword, true);

        $spuId = '12345';
        $source = 'test';

        $keywordId = $keyword->getId();
        $this->assertNotNull($keywordId, 'Keyword ID should not be null after save');
        $result = $this->keywordManager->attachToProduct($spuId, $keywordId, 2.0, $source);

        $this->assertInstanceOf(ProductKeyword::class, $result);
        $this->assertEquals($spuId, $result->getSpuId());
        $this->assertEquals($keywordId, $result->getKeyword()->getId());
        $this->assertEquals(2.0, $result->getWeight());
        $this->assertEquals($source, $result->getSource());

        // 验证关联已保存到数据库
        $savedRelation = $this->productKeywordRepository->findOneBy([
            'spuId' => $spuId,
            'keyword' => $keyword,
        ]);
        $this->assertNotNull($savedRelation);
    }

    public function testAttachToProductThrowsKeywordNotFoundException(): void
    {
        $this->expectException(KeywordNotFoundException::class);
        $this->keywordManager->attachToProduct('12345', 'non-existent-keyword', 1.0, 'test');
    }

    public function testDetachFromProductSuccessfully(): void
    {
        // 创建测试数据
        $keyword = new Keyword();
        $keyword->setKeyword('detach-test');
        $keyword->setWeight(1.0);
        $keyword->setValid(true);

        $this->keywordRepository->save($keyword, true);

        $productKeyword = new ProductKeyword();
        $productKeyword->setSpuId('12345');
        $productKeyword->setKeyword($keyword);
        $productKeyword->setWeight(1.0);
        $productKeyword->setSource('test');

        $this->productKeywordRepository->save($productKeyword, true);

        $keywordId = $keyword->getId();
        $this->assertNotNull($keywordId, 'Keyword ID should not be null after save');
        $this->keywordManager->detachFromProduct('12345', $keywordId);

        // 验证关联已被删除
        $deletedRelation = $this->productKeywordRepository->findOneBy([
            'spuId' => '12345',
            'keyword' => $keyword,
        ]);
        $this->assertNull($deletedRelation);
    }

    public function testFindByKeyword(): void
    {
        $keyword = new Keyword();
        $keyword->setKeyword('find-by-keyword-test');
        $keyword->setWeight(1.0);
        $keyword->setValid(true);

        $this->keywordRepository->save($keyword, true);

        $result = $this->keywordManager->findByKeyword('find-by-keyword-test');

        $this->assertNotNull($result);
        $this->assertEquals('find-by-keyword-test', $result->getKeyword());
        $this->assertEquals($keyword->getId(), $result->getId());

        $notFound = $this->keywordManager->findByKeyword('non-existent-keyword');
        $this->assertNull($notFound);
    }

    public function testUpdate(): void
    {
        $keyword = new Keyword();
        $keyword->setKeyword('update-test');
        $keyword->setWeight(1.0);
        $keyword->setValid(true);
        $keyword->setRecommend(false);

        $this->keywordRepository->save($keyword, true);

        $dto = new KeywordDTO(
            keyword: 'updated-keyword',
            weight: 2.5,
            parentId: null,
            valid: false,
            recommend: true,
            description: 'Updated description'
        );

        $keywordId = $keyword->getId();
        $this->assertNotNull($keywordId, 'Keyword ID should not be null after save');
        $result = $this->keywordManager->update($keywordId, $dto);

        $this->assertEquals('updated-keyword', $result->getKeyword());
        $this->assertEquals(2.5, $result->getWeight());
        $this->assertFalse($result->isValid());
        $this->assertTrue($result->isRecommend());
        $this->assertEquals('Updated description', $result->getDescription());
    }

    public function testUpdateNonExistentKeyword(): void
    {
        $dto = new KeywordDTO(
            keyword: 'non-existent',
            weight: 1.0,
            parentId: null,
            valid: true,
            recommend: false,
            description: 'Test'
        );

        $this->expectException(KeywordNotFoundException::class);
        $this->keywordManager->update('non-existent-id', $dto);
    }

    public function testUpdateWithDuplicateKeyword(): void
    {
        $keyword1 = new Keyword();
        $keyword1->setKeyword('existing-keyword');
        $keyword1->setWeight(1.0);
        $keyword1->setValid(true);
        $this->keywordRepository->save($keyword1, true);

        $keyword2 = new Keyword();
        $keyword2->setKeyword('update-keyword');
        $keyword2->setWeight(1.0);
        $keyword2->setValid(true);
        $this->keywordRepository->save($keyword2, true);

        $dto = new KeywordDTO(
            keyword: 'existing-keyword',
            weight: 2.0,
            parentId: null,
            valid: true,
            recommend: false,
            description: 'Test'
        );

        $this->expectException(DuplicateKeywordException::class);
        $keyword2Id = $keyword2->getId();
        $this->assertNotNull($keyword2Id, 'Keyword ID should not be null after save');
        $this->keywordManager->update($keyword2Id, $dto);
    }

    public function testBatchUpdateStatus(): void
    {
        $keyword1 = new Keyword();
        $keyword1->setKeyword('batch-test-1');
        $keyword1->setWeight(1.0);
        $keyword1->setValid(true);
        $this->keywordRepository->save($keyword1, true);

        $keyword2 = new Keyword();
        $keyword2->setKeyword('batch-test-2');
        $keyword2->setWeight(1.0);
        $keyword2->setValid(true);
        $this->keywordRepository->save($keyword2, true);

        $keyword1Id = $keyword1->getId();
        $keyword2Id = $keyword2->getId();
        $this->assertNotNull($keyword1Id, 'Keyword 1 ID should not be null after save');
        $this->assertNotNull($keyword2Id, 'Keyword 2 ID should not be null after save');
        $ids = [$keyword1Id, $keyword2Id];

        $updatedCount = $this->keywordManager->batchUpdateStatus($ids, false);

        $this->assertEquals(2, $updatedCount);

        // 清除实体管理器缓存以获取最新状态
        self::getEntityManager()->clear();

        $updated1 = $this->keywordRepository->find($keyword1Id);
        $updated2 = $this->keywordRepository->find($keyword2Id);

        $this->assertNotNull($updated1, 'Updated keyword 1 should be found');
        $this->assertNotNull($updated2, 'Updated keyword 2 should be found');
        $this->assertFalse($updated1->isValid());
        $this->assertFalse($updated2->isValid());
    }

    public function testBatchUpdateStatusWithEmptyIds(): void
    {
        $updatedCount = $this->keywordManager->batchUpdateStatus([], true);
        $this->assertEquals(0, $updatedCount);
    }

    public function testSearch(): void
    {
        $parent = new Keyword();
        $parent->setKeyword('parent-keyword');
        $parent->setWeight(1.0);
        $parent->setValid(true);
        $parent->setRecommend(false);
        $this->keywordRepository->save($parent, true);

        $keyword1 = new Keyword();
        $keyword1->setKeyword('search-test-1');
        $keyword1->setWeight(2.5);
        $keyword1->setValid(true);
        $keyword1->setRecommend(true);
        $keyword1->setParent($parent);
        $this->keywordRepository->save($keyword1, true);

        $keyword2 = new Keyword();
        $keyword2->setKeyword('search-test-2');
        $keyword2->setWeight(1.5);
        $keyword2->setValid(false);
        $keyword2->setRecommend(false);
        $this->keywordRepository->save($keyword2, true);

        // Test search by keyword
        $criteria = new KeywordSearchCriteria(
            keyword: 'search-test',
            limit: 10
        );
        $results = $this->keywordManager->search($criteria);
        $this->assertCount(2, $results);

        // Test search by parent
        $criteria = new KeywordSearchCriteria(
            parentId: (int) $parent->getId(),
            limit: 10
        );
        $results = $this->keywordManager->search($criteria);
        $this->assertCount(1, $results);
        $firstResult = $this->getFirstFromIterable($results);
        $this->assertNotNull($firstResult, 'First search result should not be null');
        $this->assertEquals('search-test-1', $firstResult->getKeyword());

        // Test search by valid status
        $criteria = new KeywordSearchCriteria(
            valid: true,
            keyword: 'search-test',
            limit: 10
        );
        $results = $this->keywordManager->search($criteria);
        $this->assertCount(1, $results);
        $firstResult = $this->getFirstFromIterable($results);
        $this->assertNotNull($firstResult, 'First search result should not be null');
        $this->assertEquals('search-test-1', $firstResult->getKeyword());

        // Test search by recommend status
        $criteria = new KeywordSearchCriteria(
            recommend: true,
            keyword: 'search-test',
            limit: 10
        );
        $results = $this->keywordManager->search($criteria);
        $this->assertCount(1, $results);
        $firstResult = $this->getFirstFromIterable($results);
        $this->assertNotNull($firstResult, 'First search result should not be null');
        $this->assertEquals('search-test-1', $firstResult->getKeyword());

        // Test search by weight range
        $criteria = new KeywordSearchCriteria(
            minWeight: 2.0,
            maxWeight: 3.0,
            keyword: 'search-test',
            limit: 10
        );
        $results = $this->keywordManager->search($criteria);
        $this->assertCount(1, $results);
        $firstResult = $this->getFirstFromIterable($results);
        $this->assertNotNull($firstResult, 'First search result should not be null');
        $this->assertEquals('search-test-1', $firstResult->getKeyword());
    }
}
