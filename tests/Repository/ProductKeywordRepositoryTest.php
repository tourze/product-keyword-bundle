<?php

namespace ProductKeywordBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ProductKeywordBundle\Entity\Keyword;
use ProductKeywordBundle\Entity\ProductKeyword;
use ProductKeywordBundle\Repository\ProductKeywordRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(ProductKeywordRepository::class)]
#[RunTestsInSeparateProcesses]
final class ProductKeywordRepositoryTest extends AbstractRepositoryTestCase
{
    private ProductKeywordRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getContainer()->get(ProductKeywordRepository::class);
        $this->assertInstanceOf(ProductKeywordRepository::class, $repository);
        $this->repository = $repository;
    }

    protected function createNewEntity(): object
    {
        $keyword = new Keyword();
        $keyword->setKeyword('test-keyword-' . uniqid());
        $keyword->setWeight(1.0);
        $keyword->setValid(true);

        $productKeyword = new ProductKeyword();
        $productKeyword->setSpuId('test-spu-' . uniqid());
        $productKeyword->setKeyword($keyword);
        $productKeyword->setWeight(1.0);
        $productKeyword->setSource('manual');

        return $productKeyword;
    }

    /**
     * @return ServiceEntityRepository<ProductKeyword>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    public function testFindByProduct(): void
    {
        $spuId = 'test-product-' . uniqid();
        $productKeyword1 = $this->createProductKeyword($spuId, 'keyword1-' . uniqid());
        $productKeyword2 = $this->createProductKeyword($spuId, 'keyword2-' . uniqid());
        $productKeyword3 = $this->createProductKeyword('other-spu', 'keyword3-' . uniqid());

        $this->repository->saveAll([$productKeyword1, $productKeyword2, $productKeyword3]);

        $results = $this->repository->findByProduct($spuId, 1, 10);

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertSame($spuId, $result->getSpuId());
        }
    }

    public function testFindByProductWithPagination(): void
    {
        $spuId = 'paginated-product-' . uniqid();

        // 创建5个关联
        for ($i = 1; $i <= 5; ++$i) {
            $productKeyword = $this->createProductKeyword($spuId, "keyword-{$i}-" . uniqid());
            $this->repository->save($productKeyword, false);
        }
        $this->repository->flush();

        // 测试分页：每页3个，第1页
        $page1Results = $this->repository->findByProduct($spuId, 1, 3);
        $this->assertCount(3, $page1Results);

        // 测试分页：每页3个，第2页
        $page2Results = $this->repository->findByProduct($spuId, 2, 3);
        $this->assertCount(2, $page2Results);
    }

    public function testFindByKeywordId(): void
    {
        $keyword = $this->createKeyword('shared-keyword-' . uniqid());
        // 保存关键词到数据库以获取ID
        self::getEntityManager()->persist($keyword);
        self::getEntityManager()->flush();

        $productKeyword1 = $this->createProductKeyword('spu1-' . uniqid());
        $productKeyword1->setKeyword($keyword);
        $productKeyword2 = $this->createProductKeyword('spu2-' . uniqid());
        $productKeyword2->setKeyword($keyword);
        $productKeyword3 = $this->createProductKeyword('spu3-' . uniqid());
        // productKeyword3使用不同的关键词

        $this->repository->saveAll([$productKeyword1, $productKeyword2, $productKeyword3]);

        $keywordId = $keyword->getId();
        $this->assertNotNull($keywordId);
        $this->assertNotEmpty($keywordId); // 验证 Snowflake ID 不为空
        $results = $this->repository->findByKeywordId($keywordId, 1, 10);

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertSame($keyword->getId(), $result->getKeyword()->getId());
        }
    }

    public function testFindByKeywordIdWithPagination(): void
    {
        $keyword = $this->createKeyword('paginated-shared-keyword-' . uniqid());
        self::getEntityManager()->persist($keyword);
        self::getEntityManager()->flush();

        // 创建4个使用相同关键词的产品关联
        for ($i = 1; $i <= 4; ++$i) {
            $productKeyword = $this->createProductKeyword("paginated-spu-{$i}-" . uniqid());
            $productKeyword->setKeyword($keyword);
            $this->repository->save($productKeyword, false);
        }
        $this->repository->flush();

        // 测试分页：每页2个，第1页
        $keywordId = $keyword->getId();
        $this->assertNotNull($keywordId);
        $this->assertNotEmpty($keywordId); // 验证 Snowflake ID 不为空
        $page1Results = $this->repository->findByKeywordId($keywordId, 1, 2);
        $this->assertCount(2, $page1Results);

        // 测试分页：每页2个，第2页
        $page2Results = $this->repository->findByKeywordId($keywordId, 2, 2);
        $this->assertCount(2, $page2Results);
    }

    public function testFindByProductOrderByWeight(): void
    {
        $spuId = 'weighted-product-' . uniqid();
        $productKeyword1 = $this->createProductKeyword($spuId, 'keyword1-' . uniqid(), 1.0);
        $productKeyword2 = $this->createProductKeyword($spuId, 'keyword2-' . uniqid(), 5.0);
        $productKeyword3 = $this->createProductKeyword($spuId, 'keyword3-' . uniqid(), 3.0);

        $this->repository->saveAll([$productKeyword1, $productKeyword2, $productKeyword3]);

        $results = $this->repository->findByProductOrderByWeight($spuId, 1, 10);

        $this->assertCount(3, $results);
        // 验证按权重降序排列
        $this->assertSame(5.0, $results[0]->getWeight());
        $this->assertSame(3.0, $results[1]->getWeight());
        $this->assertSame(1.0, $results[2]->getWeight());
    }

    /**
     * @param array{source: string, expected: int} $data
     */
    #[DataProvider('findBySourceProvider')]
    public function testFindBySource(array $data): void
    {
        // 创建不同来源的产品关键词关联
        $productKeyword1 = $this->createProductKeyword('spu1-' . uniqid(), 'keyword1-' . uniqid(), 1.0, 'manual');
        $productKeyword2 = $this->createProductKeyword('spu2-' . uniqid(), 'keyword2-' . uniqid(), 1.0, 'auto');
        $productKeyword3 = $this->createProductKeyword('spu3-' . uniqid(), 'keyword3-' . uniqid(), 1.0, 'import');
        $productKeyword4 = $this->createProductKeyword('spu4-' . uniqid(), 'keyword4-' . uniqid(), 1.0, 'manual');

        $this->repository->saveAll([$productKeyword1, $productKeyword2, $productKeyword3, $productKeyword4]);

        $results = $this->repository->findBySource($data['source'], 1, 10);

        $testResults = array_filter($results, function (ProductKeyword $pk) {
            $spuId = $pk->getSpuId();
            $keyword = $pk->getKeyword()->getKeyword();

            return str_contains($spuId, 'spu')
                   && null !== $keyword && str_contains($keyword, 'keyword');
        });
        $this->assertCount($data['expected'], $testResults);

        foreach ($testResults as $result) {
            $this->assertSame($data['source'], $result->getSource());
        }
    }

    /**
     * @return array<string, array{data: array{source: string, expected: int}}>
     */
    public static function findBySourceProvider(): array
    {
        return [
            'manual source' => [
                'data' => ['source' => 'manual', 'expected' => 2],
            ],
            'auto source' => [
                'data' => ['source' => 'auto', 'expected' => 1],
            ],
            'import source' => [
                'data' => ['source' => 'import', 'expected' => 1],
            ],
        ];
    }

    public function testFindManualKeywords(): void
    {
        $spuId = 'manual-test-product-' . uniqid();
        $productKeyword1 = $this->createProductKeyword($spuId, 'manual-keyword1-' . uniqid(), 1.0, 'manual');
        $productKeyword2 = $this->createProductKeyword($spuId, 'auto-keyword-' . uniqid(), 1.0, 'auto');
        $productKeyword3 = $this->createProductKeyword($spuId, 'manual-keyword2-' . uniqid(), 1.0, 'manual');

        $this->repository->saveAll([$productKeyword1, $productKeyword2, $productKeyword3]);

        $results = $this->repository->findManualKeywords($spuId, 1, 10);

        $this->assertCount(2, $results);
        foreach ($results as $result) {
            $this->assertSame($spuId, $result->getSpuId());
            $this->assertSame('manual', $result->getSource());
        }
    }

    public function testDeleteByProduct(): void
    {
        $spuId = 'delete-test-product-' . uniqid();
        $otherSpuId = 'other-product-' . uniqid();

        $productKeyword1 = $this->createProductKeyword($spuId, 'keyword1-' . uniqid());
        $productKeyword2 = $this->createProductKeyword($spuId, 'keyword2-' . uniqid());
        $productKeyword3 = $this->createProductKeyword($otherSpuId, 'keyword3-' . uniqid());

        $this->repository->saveAll([$productKeyword1, $productKeyword2, $productKeyword3]);

        $deletedCount = $this->repository->deleteByProduct($spuId);

        $this->assertSame(2, $deletedCount);

        // 验证删除结果
        $remainingResults = $this->repository->findByProduct($spuId);
        $this->assertCount(0, $remainingResults);

        // 验证其他产品的关联没有被删除
        $otherResults = $this->repository->findByProduct($otherSpuId);
        $this->assertCount(1, $otherResults);
    }

    public function testDeleteBySource(): void
    {
        $spuId = 'source-delete-test-product-' . uniqid();

        $productKeyword1 = $this->createProductKeyword($spuId, 'manual-keyword-' . uniqid(), 1.0, 'manual');
        $productKeyword2 = $this->createProductKeyword($spuId, 'auto-keyword-' . uniqid(), 1.0, 'auto');
        $productKeyword3 = $this->createProductKeyword($spuId, 'manual-keyword2-' . uniqid(), 1.0, 'manual');

        $this->repository->saveAll([$productKeyword1, $productKeyword2, $productKeyword3]);

        $deletedCount = $this->repository->deleteBySource($spuId, 'manual');

        $this->assertSame(2, $deletedCount);

        // 验证manual来源的关联被删除
        $manualResults = $this->repository->findBySource('manual');
        $testManualResults = array_filter($manualResults, function (ProductKeyword $pk) use ($spuId) {
            return $pk->getSpuId() === $spuId;
        });
        $this->assertCount(0, $testManualResults);

        // 验证auto来源的关联没有被删除
        $autoResults = $this->repository->findBySource('auto');
        $testAutoResults = array_filter($autoResults, function (ProductKeyword $pk) use ($spuId) {
            return $pk->getSpuId() === $spuId;
        });
        $this->assertCount(1, $testAutoResults);
    }

    public function testSaveAll(): void
    {
        $productKeywords = [
            $this->createProductKeyword('batch-spu1-' . uniqid(), 'batch-keyword1-' . uniqid()),
            $this->createProductKeyword('batch-spu2-' . uniqid(), 'batch-keyword2-' . uniqid()),
            $this->createProductKeyword('batch-spu3-' . uniqid(), 'batch-keyword3-' . uniqid()),
        ];

        $this->repository->saveAll($productKeywords);

        foreach ($productKeywords as $productKeyword) {
            $found = $this->repository->findByProduct($productKeyword->getSpuId());
            $this->assertCount(1, $found);
            $this->assertSame($productKeyword->getSpuId(), $found[0]->getSpuId());
        }
    }

    public function testSaveAllWithoutFlush(): void
    {
        $productKeywords = [
            $this->createProductKeyword('no-flush-spu1-' . uniqid(), 'no-flush-keyword1-' . uniqid()),
            $this->createProductKeyword('no-flush-spu2-' . uniqid(), 'no-flush-keyword2-' . uniqid()),
        ];

        $this->repository->saveAll($productKeywords, false);

        // 在flush之前，应该找不到这些关联
        $found1 = $this->repository->findByProduct($productKeywords[0]->getSpuId());
        $this->assertCount(0, $found1);

        // 手动flush后应该能找到
        $this->repository->flush();
        $found2 = $this->repository->findByProduct($productKeywords[0]->getSpuId());
        $this->assertCount(1, $found2);
    }

    public function testFlush(): void
    {
        $productKeyword = $this->createProductKeyword('flush-test-spu-' . uniqid(), 'flush-test-keyword-' . uniqid());

        // 保存但不flush
        $this->repository->save($productKeyword, false);

        // 验证flush前找不到
        $found1 = $this->repository->findByProduct($productKeyword->getSpuId());
        $this->assertCount(0, $found1);

        // 执行flush
        $this->repository->flush();

        // 验证flush后能找到
        $found2 = $this->repository->findByProduct($productKeyword->getSpuId());
        $this->assertCount(1, $found2);
    }

    public function testClear(): void
    {
        $productKeyword = $this->createProductKeyword('clear-test-spu-' . uniqid(), 'clear-test-keyword-' . uniqid());
        $this->repository->save($productKeyword);

        // 修改实体
        $productKeyword->setWeight(5.0);

        // 清空实体管理器
        $this->repository->clear();

        // 重新获取实体，应该是数据库中的原始值
        $found = $this->repository->findByProduct($productKeyword->getSpuId());
        $this->assertCount(1, $found);
        $this->assertSame(1.0, $found[0]->getWeight()); // 应该是原始值，不是修改后的5.0
    }

    private function createKeyword(string $keyword, float $weight = 1.0, bool $valid = true): Keyword
    {
        $entity = new Keyword();
        $entity->setKeyword($keyword);
        $entity->setWeight($weight);
        $entity->setValid($valid);
        $entity->setParent(null);

        return $entity;
    }

    private function createProductKeyword(string $spuId, ?string $keywordText = null, float $weight = 1.0, string $source = 'manual'): ProductKeyword
    {
        $keyword = $this->createKeyword($keywordText ?? 'test-keyword-' . uniqid());

        $productKeyword = new ProductKeyword();
        $productKeyword->setSpuId($spuId);
        $productKeyword->setKeyword($keyword);
        $productKeyword->setWeight($weight);
        $productKeyword->setSource($source);

        return $productKeyword;
    }
}
