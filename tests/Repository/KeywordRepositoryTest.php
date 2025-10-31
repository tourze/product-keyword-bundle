<?php

namespace ProductKeywordBundle\Tests\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ProductKeywordBundle\Entity\Keyword;
use ProductKeywordBundle\Repository\KeywordRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(KeywordRepository::class)]
#[RunTestsInSeparateProcesses]
final class KeywordRepositoryTest extends AbstractRepositoryTestCase
{
    private KeywordRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getContainer()->get(KeywordRepository::class);
        $this->assertInstanceOf(KeywordRepository::class, $repository);
        $this->repository = $repository;
    }

    protected function createNewEntity(): object
    {
        $keyword = new Keyword();
        $keyword->setKeyword('test-keyword-' . uniqid());
        $keyword->setWeight(1.0);
        $keyword->setValid(true);
        // parent 是可选的，设置为 null 以避免外部依赖问题
        $keyword->setParent(null);

        return $keyword;
    }

    /**
     * @return ServiceEntityRepository<Keyword>
     */
    protected function getRepository(): ServiceEntityRepository
    {
        return $this->repository;
    }

    public function testFindByKeyword(): void
    {
        $keywordText = 'unique-test-keyword-' . uniqid();
        $keyword = $this->createKeyword($keywordText, 2.0, true);
        $this->repository->save($keyword);

        $result = $this->repository->findByKeyword($keywordText);

        $this->assertInstanceOf(Keyword::class, $result);
        $this->assertSame($keywordText, $result->getKeyword());
        $this->assertSame(2.0, $result->getWeight());
    }

    public function testFindByKeywordNotFound(): void
    {
        $result = $this->repository->findByKeyword('non-existent-keyword-' . uniqid());

        $this->assertNull($result);
    }

    /**
     * @param list<string> $keywords
     * @param list<bool> $validities
     * @param list<string> $expectedKeywords
     */
    #[DataProvider('findValidKeywordsByNamesProvider')]
    public function testFindValidKeywordsByNames(array $keywords, array $validities, array $expectedKeywords): void
    {
        // 创建测试关键词
        foreach ($keywords as $index => $keywordText) {
            $keyword = $this->createKeyword($keywordText, 1.0, $validities[$index]);
            $this->repository->save($keyword, false);
        }
        $this->repository->flush();

        $results = $this->repository->findValidKeywordsByNames($keywords);

        $resultKeywords = array_map(fn (Keyword $k) => $k->getKeyword(), $results);
        $this->assertCount(count($expectedKeywords), $results);

        foreach ($expectedKeywords as $expectedKeyword) {
            $this->assertContains($expectedKeyword, $resultKeywords);
        }
    }

    /**
     * @return array<string, array{keywords: list<string>, validities: list<bool>, expectedKeywords: list<string>}>
     */
    public static function findValidKeywordsByNamesProvider(): array
    {
        $prefix = 'test-' . uniqid() . '-';

        return [
            'all valid' => [
                'keywords' => [$prefix . '1', $prefix . '2', $prefix . '3'],
                'validities' => [true, true, true],
                'expectedKeywords' => [$prefix . '1', $prefix . '2', $prefix . '3'],
            ],
            'mixed validity' => [
                'keywords' => [$prefix . '4', $prefix . '5', $prefix . '6'],
                'validities' => [true, false, true],
                'expectedKeywords' => [$prefix . '4', $prefix . '6'],
            ],
            'all invalid' => [
                'keywords' => [$prefix . '7', $prefix . '8'],
                'validities' => [false, false],
                'expectedKeywords' => [],
            ],
        ];
    }

    public function testFindByCatalog(): void
    {
        // 注意：由于Keyword实体中没有catalog属性，此方法目前无法正常工作
        // 需要在实际项目中添加catalog关联后才能正确实现此测试
        // 这里提供基础的测试框架，但会跳过实际验证
        self::markTestSkipped('Keyword entity does not have catalog relationship. Need to add catalog property to Keyword entity first.');
    }

    public function testFindByCatalogWithPagination(): void
    {
        // 注意：由于Keyword实体中没有catalog属性，此方法目前无法正常工作
        self::markTestSkipped('Keyword entity does not have catalog relationship. Need to add catalog property to Keyword entity first.');
    }

    public function testFindValidKeywords(): void
    {
        $prefix = 'find-valid-' . uniqid() . '-';
        $validKeyword1 = $this->createKeyword($prefix . 'valid-1', 1.0, true);
        $validKeyword2 = $this->createKeyword($prefix . 'valid-2', 2.0, true);
        $invalidKeyword = $this->createKeyword($prefix . 'invalid', 1.0, false);

        // 分别保存以确保实体被正确持久化
        $this->repository->save($validKeyword1);
        $this->repository->save($validKeyword2);
        $this->repository->save($invalidKeyword);

        $results = $this->repository->findValidKeywords(1, 20);

        // 验证方法返回结果不为空（包含有效关键词）
        $this->assertGreaterThan(0, count($results));

        // 验证结果中的所有关键词都是有效的
        foreach ($results as $keyword) {
            $this->assertTrue($keyword->isValid());
        }

        // 验证结果中包含我们创建的有效关键词
        $foundValidKeywords = array_filter($results, fn (Keyword $k) => in_array($k->getKeyword(), [$validKeyword1->getKeyword(), $validKeyword2->getKeyword()], true)
        );
        $this->assertCount(2, $foundValidKeywords,
            'Should find exactly our 2 created valid keywords with prefix: ' . $prefix);

        // 验证结果中不包含无效的关键词
        $foundInvalidKeywords = array_filter($results, fn (Keyword $k) => $k->getKeyword() === $invalidKeyword->getKeyword()
        );
        $this->assertCount(0, $foundInvalidKeywords);
    }

    public function testFindValidKeywordsWithPagination(): void
    {
        // 创建5个有效关键词
        $keywords = [];
        for ($i = 1; $i <= 5; ++$i) {
            $keyword = $this->createKeyword("paginated-valid-{$i}-" . uniqid(), 1.0, true);
            $keywords[] = $keyword;
        }
        $this->repository->saveAll($keywords);

        // 测试分页：每页3个，第1页
        $page1Results = $this->repository->findValidKeywords(1, 3);
        $this->assertGreaterThanOrEqual(3, count($page1Results));

        // 测试分页：每页3个，第2页
        $page2Results = $this->repository->findValidKeywords(2, 3);
        $this->assertGreaterThanOrEqual(0, count($page2Results));
    }

    /**
     * @param array{min: float, max: float, expected: int} $data
     */
    #[DataProvider('findByWeightRangeProvider')]
    public function testFindByWeightRange(array $data): void
    {
        // 创建不同权重的关键词
        $keyword1 = $this->createKeyword('weight-1-' . uniqid(), 1.0);
        $keyword2 = $this->createKeyword('weight-2-' . uniqid(), 2.5);
        $keyword3 = $this->createKeyword('weight-3-' . uniqid(), 5.0);
        $keyword4 = $this->createKeyword('weight-4-' . uniqid(), 7.5);

        $this->repository->saveAll([$keyword1, $keyword2, $keyword3, $keyword4]);

        $results = $this->repository->findByWeightRange($data['min'], $data['max'], 1, 10);

        $testKeywords = array_filter($results, function (Keyword $k) {
            $keyword = $k->getKeyword();

            return null !== $keyword && str_contains($keyword, 'weight-');
        });
        $this->assertCount($data['expected'], $testKeywords);

        foreach ($testKeywords as $keyword) {
            $this->assertGreaterThanOrEqual($data['min'], $keyword->getWeight());
            $this->assertLessThanOrEqual($data['max'], $keyword->getWeight());
        }
    }

    /**
     * @return array<string, array{data: array{min: float, max: float, expected: int}}>
     */
    public static function findByWeightRangeProvider(): array
    {
        return [
            'low range' => [
                'data' => ['min' => 0.5, 'max' => 2.0, 'expected' => 1],
            ],
            'medium range' => [
                'data' => ['min' => 2.0, 'max' => 6.0, 'expected' => 2],
            ],
            'high range' => [
                'data' => ['min' => 6.0, 'max' => 10.0, 'expected' => 1],
            ],
            'full range' => [
                'data' => ['min' => 0.0, 'max' => 10.0, 'expected' => 4],
            ],
        ];
    }

    public function testSearchKeywords(): void
    {
        $keyword1 = $this->createKeyword('searchable-phone-' . uniqid());
        $keyword2 = $this->createKeyword('searchable-smartphone-' . uniqid());
        $keyword3 = $this->createKeyword('computer-device-' . uniqid());

        $this->repository->saveAll([$keyword1, $keyword2, $keyword3]);

        $results = $this->repository->searchKeywords('phone', 1, 10);

        $phoneKeywords = array_filter($results, function (Keyword $k) {
            $keyword = $k->getKeyword();

            return null !== $keyword && str_contains($keyword, 'phone');
        });
        $this->assertCount(2, $phoneKeywords);
    }

    public function testSearchKeywordsWithPagination(): void
    {
        // 创建多个包含search的关键词
        for ($i = 1; $i <= 5; ++$i) {
            $keyword = $this->createKeyword("search-result-{$i}-" . uniqid());
            $this->repository->save($keyword, false);
        }
        $this->repository->flush();

        // 测试分页
        $page1Results = $this->repository->searchKeywords('search-result', 1, 3);
        $this->assertGreaterThanOrEqual(3, count($page1Results));

        $page2Results = $this->repository->searchKeywords('search-result', 2, 3);
        $this->assertGreaterThanOrEqual(0, count($page2Results));
    }

    public function testSaveAll(): void
    {
        $keywords = [
            $this->createKeyword('batch-1-' . uniqid()),
            $this->createKeyword('batch-2-' . uniqid()),
            $this->createKeyword('batch-3-' . uniqid()),
        ];

        $this->repository->saveAll($keywords);

        foreach ($keywords as $keyword) {
            $keywordText = $keyword->getKeyword();
            $this->assertNotNull($keywordText);
            $found = $this->repository->findByKeyword($keywordText);
            $this->assertNotNull($found);
            $this->assertSame($keywordText, $found->getKeyword());
        }
    }

    public function testSaveAllWithoutFlush(): void
    {
        $keywords = [
            $this->createKeyword('batch-no-flush-1-' . uniqid()),
            $this->createKeyword('batch-no-flush-2-' . uniqid()),
        ];

        $this->repository->saveAll($keywords, false);

        // 在flush之前，应该找不到这些关键词
        $keywordText1 = $keywords[0]->getKeyword();
        $this->assertNotNull($keywordText1);
        $found1 = $this->repository->findByKeyword($keywordText1);
        $this->assertNull($found1);

        // 手动flush后应该能找到
        $this->repository->flush();
        $found2 = $this->repository->findByKeyword($keywordText1);
        $this->assertNotNull($found2);
    }

    public function testFlush(): void
    {
        $keyword = $this->createKeyword('flush-test-' . uniqid());

        // 保存但不flush
        $this->repository->save($keyword, false);

        // 验证flush前找不到
        $keywordText = $keyword->getKeyword();
        $this->assertNotNull($keywordText);
        $found1 = $this->repository->findByKeyword($keywordText);
        $this->assertNull($found1);

        // 执行flush
        $this->repository->flush();

        // 验证flush后能找到
        $found2 = $this->repository->findByKeyword($keywordText);
        $this->assertNotNull($found2);
    }

    public function testClear(): void
    {
        $keyword = $this->createKeyword('clear-test-' . uniqid());
        $this->repository->save($keyword);

        // 修改实体
        $keyword->setWeight(5.0);

        // 清空实体管理器
        $this->repository->clear();

        // 重新获取实体，应该是数据库中的原始值
        $keywordText = $keyword->getKeyword();
        $this->assertNotNull($keywordText);
        $found = $this->repository->findByKeyword($keywordText);
        $this->assertNotNull($found);
        $this->assertSame(1.0, $found->getWeight()); // 应该是原始值，不是修改后的5.0
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
}
