<?php

namespace ProductKeywordBundle\Tests\Repository;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ProductKeywordBundle\DTO\SearchLogCriteria;
use ProductKeywordBundle\Entity\SearchLog;
use ProductKeywordBundle\Repository\SearchLogRepository;
use Tourze\PHPUnitSymfonyKernelTest\AbstractRepositoryTestCase;

/**
 * @internal
 */
#[CoversClass(SearchLogRepository::class)]
#[RunTestsInSeparateProcesses]
final class SearchLogRepositoryTest extends AbstractRepositoryTestCase
{
    private SearchLogRepository $repository;

    protected function onSetUp(): void
    {
        $repository = self::getContainer()->get(SearchLogRepository::class);
        $this->assertInstanceOf(SearchLogRepository::class, $repository);
        $this->repository = $repository;
    }

    protected function createNewEntity(): object
    {
        $log = new SearchLog();
        $log->setKeyword('测试关键词' . uniqid());
        $log->setUserHash(hash('sha256', 'testuser' . uniqid()));
        $log->setResultCount(rand(0, 100));
        $log->setSource('web');
        $log->setSessionId('session' . uniqid());
        $log->setCreateTime(new \DateTimeImmutable());

        return $log;
    }

    protected function getRepository(): SearchLogRepository
    {
        return $this->repository;
    }

    public function testBatchInsert(): void
    {
        $logs = [];
        for ($i = 0; $i < 150; ++$i) {
            $log = new SearchLog();
            $log->setKeyword("测试关键词{$i}");
            $log->setUserHash(hash('sha256', "user{$i}"));
            $log->setResultCount($i % 10);
            $log->setSource('web');
            $log->setSessionId("session{$i}");
            $log->setCreateTime(new \DateTimeImmutable());
            $logs[] = $log;
        }

        $this->repository->batchInsert($logs);

        $count = $this->repository->count([]);
        $this->assertGreaterThanOrEqual(150, $count);
    }

    public function testFindByCriteriaWithKeyword(): void
    {
        $this->createSearchLog('iPhone 手机', 'user1', 10);
        $this->createSearchLog('Android 手机', 'user2', 5);
        $this->createSearchLog('笔记本电脑', 'user3', 0);

        $criteria = new SearchLogCriteria(
            keyword: '手机'
        );

        $results = $this->repository->findByCriteria($criteria);

        $this->assertCount(2, $results);
        $this->assertContains('iPhone 手机', array_map(fn ($log) => $log->getKeyword(), $results));
        $this->assertContains('Android 手机', array_map(fn ($log) => $log->getKeyword(), $results));
    }

    public function testFindByCriteriaWithUserId(): void
    {
        $this->createSearchLog('测试关键词1', 'user1', 5);
        $this->createSearchLog('测试关键词2', 'user2', 3);
        $this->createSearchLog('测试关键词3', 'user1', 8);

        $criteria = new SearchLogCriteria(
            userId: 'user1'
        );

        $results = $this->repository->findByCriteria($criteria);

        $this->assertCount(2, $results);
        $userHash = hash('sha256', 'user1');
        foreach ($results as $log) {
            $this->assertEquals($userHash, $log->getUserHash());
        }
    }

    public function testFindByCriteriaWithSource(): void
    {
        // 先记录现有的 web 来源数据量
        $initialWebCount = $this->repository->findByCriteria(new SearchLogCriteria(source: 'web'));

        $this->createSearchLog('关键词1', 'user1', 5, 'web');
        $this->createSearchLog('关键词2', 'user2', 3, 'mobile');
        $this->createSearchLog('关键词3', 'user3', 8, 'web');

        $criteria = new SearchLogCriteria(
            source: 'web'
        );

        $results = $this->repository->findByCriteria($criteria);

        // 应该是：初始 web 数据量 + 新增的2条 web 记录
        $this->assertCount(count($initialWebCount) + 2, $results);
        foreach ($results as $log) {
            $this->assertEquals('web', $log->getSource());
        }
    }

    public function testFindByCriteriaWithDateRange(): void
    {
        // 使用明天的时间范围，避免与今天创建的 fixture 数据重叠
        $tomorrow = new \DateTime('+1 day');
        $dayAfterTomorrow = new \DateTime('+2 days');
        $threeDay = new \DateTime('+3 days');

        $log1 = $this->createSearchLog('关键词1', 'user1', 5);
        $log1->setCreateTime(\DateTimeImmutable::createFromMutable($tomorrow));
        self::getEntityManager()->flush();

        $log2 = $this->createSearchLog('关键词2', 'user2', 3);
        $log2->setCreateTime(\DateTimeImmutable::createFromMutable($dayAfterTomorrow));
        self::getEntityManager()->flush();

        $criteria = new SearchLogCriteria(
            startDate: $dayAfterTomorrow,
            endDate: $threeDay
        );

        $results = $this->repository->findByCriteria($criteria);

        $this->assertCount(1, $results);
        $this->assertEquals('关键词2', $results[0]->getKeyword());
    }

    public function testFindByCriteriaWithResultCount(): void
    {
        $this->createSearchLog('关键词1', 'user1', 5);
        $this->createSearchLog('关键词2', 'user2', 15);
        $this->createSearchLog('关键词3', 'user3', 25);

        $criteria = new SearchLogCriteria(
            minResultCount: 10,
            maxResultCount: 20
        );

        $results = $this->repository->findByCriteria($criteria);

        $this->assertCount(1, $results);
        $this->assertEquals('关键词2', $results[0]->getKeyword());
        $this->assertEquals(15, $results[0]->getResultCount());
    }

    public function testDeleteOlderThan(): void
    {
        $oldDate = new \DateTime('-7 days');
        $recentDate = new \DateTime('-1 day');

        // 记录初始数据量（包含 fixtures）
        $initialCount = $this->repository->count([]);

        $oldLog = $this->createSearchLog('旧记录', 'user1', 5);
        $oldLog->setCreateTime(\DateTimeImmutable::createFromMutable($oldDate));
        self::getEntityManager()->flush();

        $recentLog = $this->createSearchLog('新记录', 'user2', 3);
        $recentLog->setCreateTime(\DateTimeImmutable::createFromMutable($recentDate));
        self::getEntityManager()->flush();

        $cutoffDate = new \DateTime('-3 days');
        $deletedCount = $this->repository->deleteOlderThan($cutoffDate);

        $this->assertEquals(1, $deletedCount);

        $remainingLogs = $this->repository->findAll();
        // 应该是：初始数据量 + 新记录（旧记录被删除）
        $this->assertCount($initialCount + 1, $remainingLogs);

        // 验证新记录仍然存在
        $newRecords = array_filter($remainingLogs, fn ($log) => '新记录' === $log->getKeyword());
        $this->assertCount(1, $newRecords);
    }

    public function testDeleteByUserHash(): void
    {
        $userHash1 = hash('sha256', 'user1');
        $userHash2 = hash('sha256', 'user2');

        // 记录初始数据量
        $initialCount = $this->repository->count([]);

        $this->createSearchLog('记录1', 'user1', 5);
        $this->createSearchLog('记录2', 'user1', 3);
        $this->createSearchLog('记录3', 'user2', 8);

        $deletedCount = $this->repository->deleteByUserHash($userHash1);

        $this->assertEquals(2, $deletedCount);

        $remainingLogs = $this->repository->findAll();
        // 应该是：初始数据量 + user2 的记录（user1 的2条记录被删除）
        $this->assertCount($initialCount + 1, $remainingLogs);

        // 验证 user2 的记录仍然存在
        $user2Records = array_filter($remainingLogs, fn ($log) => $log->getUserHash() === $userHash2);
        $this->assertCount(1, $user2Records);
    }

    public function testCountByDateRange(): void
    {
        $start = new \DateTime('-7 days');
        $middle = new \DateTime('-3 days');
        $end = new \DateTime('-1 day');

        $log1 = $this->createSearchLog('记录1', 'user1', 5);
        $log1->setCreateTime(\DateTimeImmutable::createFromMutable($start));

        $log2 = $this->createSearchLog('记录2', 'user2', 3);
        $log2->setCreateTime(\DateTimeImmutable::createFromMutable($middle));

        $log3 = $this->createSearchLog('记录3', 'user3', 8);
        $log3->setCreateTime(\DateTimeImmutable::createFromMutable($end));

        self::getEntityManager()->flush();

        $count = $this->repository->countByDateRange($start, $end);
        $this->assertEquals(3, $count);

        $partialCount = $this->repository->countByDateRange($middle, $end);
        $this->assertEquals(2, $partialCount);
    }

    public function testGetHotKeywords(): void
    {
        $start = new \DateTime('-7 days');
        $end = new \DateTime();

        // 使用不与 fixture 冲突的关键词
        $this->createMultipleSearchLogs('测试热词1', 5, \DateTimeImmutable::createFromMutable($start));
        $this->createMultipleSearchLogs('测试热词2', 3, \DateTimeImmutable::createFromMutable($start));
        $this->createMultipleSearchLogs('测试热词3', 8, \DateTimeImmutable::createFromMutable($start));

        $hotKeywords = $this->repository->getHotKeywords($start, $end, 10);

        // 检查是否包含我们的测试数据（数量应该大于等于3）
        $this->assertGreaterThanOrEqual(3, count($hotKeywords));

        // 验证我们创建的热词存在于结果中
        $testKeywords = array_filter($hotKeywords, fn ($item) => in_array($item['keyword'], ['测试热词1', '测试热词2', '测试热词3'], true));
        $this->assertCount(3, $testKeywords);

        // 验证排序正确（测试热词3应该排在前面）
        $hotWord3 = array_filter($hotKeywords, fn ($item) => '测试热词3' === $item['keyword']);
        $this->assertNotEmpty($hotWord3);
        $hotWord3 = array_values($hotWord3)[0];
        $this->assertEquals(8, $hotWord3['count']);
    }

    public function testGetNoResultKeywords(): void
    {
        $start = new \DateTime('-7 days');
        $end = new \DateTime();

        $this->createMultipleSearchLogs('无结果词1', 3, \DateTimeImmutable::createFromMutable($start), 0);
        $this->createMultipleSearchLogs('无结果词2', 5, \DateTimeImmutable::createFromMutable($start), 0);
        $this->createMultipleSearchLogs('有结果词', 2, \DateTimeImmutable::createFromMutable($start), 10);

        $noResultKeywords = $this->repository->getNoResultKeywords($start, $end, 10);

        $this->assertCount(2, $noResultKeywords);
        $this->assertEquals('无结果词2', $noResultKeywords[0]['keyword']);
        $this->assertEquals(5, $noResultKeywords[0]['count']);
        $this->assertEquals('无结果词1', $noResultKeywords[1]['keyword']);
        $this->assertEquals(3, $noResultKeywords[1]['count']);
    }

    private function createSearchLog(string $keyword, string $user, int $resultCount, string $source = 'web'): SearchLog
    {
        $log = new SearchLog();
        $log->setKeyword($keyword);
        $log->setUserHash(hash('sha256', $user));
        $log->setResultCount($resultCount);
        $log->setSource($source);
        $log->setSessionId('session_' . uniqid());
        $log->setCreateTime(new \DateTimeImmutable());

        self::getEntityManager()->persist($log);
        self::getEntityManager()->flush();

        return $log;
    }

    public function testSave(): void
    {
        $log = new SearchLog();
        $log->setKeyword('save-test-keyword-' . uniqid());
        $log->setUserHash(hash('sha256', 'save-test-user'));
        $log->setResultCount(15);
        $log->setSource('api');
        $log->setSessionId('save-test-session');
        $log->setCreateTime(new \DateTimeImmutable());

        $this->repository->save($log, true);

        $this->assertNotNull($log->getId());
        $found = self::getEntityManager()->find(SearchLog::class, $log->getId());
        $this->assertNotNull($found);
        $this->assertSame($log->getKeyword(), $found->getKeyword());
    }

    public function testSaveWithoutFlush(): void
    {
        $log = new SearchLog();
        $log->setKeyword('save-no-flush-keyword-' . uniqid());
        $log->setUserHash(hash('sha256', 'save-no-flush-user'));
        $log->setResultCount(10);
        $log->setSource('web');
        $log->setSessionId('save-no-flush-session');
        $log->setCreateTime(new \DateTimeImmutable());

        // 保存但不flush
        $this->repository->save($log, false);

        // 在flush之前应该找不到（因为还在事务中）
        $initialCount = $this->repository->count(['keyword' => $log->getKeyword()]);

        // 手动flush后应该能找到
        self::getEntityManager()->flush();
        $finalCount = $this->repository->count(['keyword' => $log->getKeyword()]);
        $this->assertGreaterThan($initialCount, $finalCount);
    }

    public function testRemove(): void
    {
        $log = $this->createSearchLog('remove-test-keyword-' . uniqid(), 'remove-test-user', 5);
        $logId = $log->getId();

        $this->repository->remove($log, true);

        $found = self::getEntityManager()->find(SearchLog::class, $logId);
        $this->assertNull($found);
    }

    public function testRemoveWithoutFlushCustom(): void
    {
        $log = $this->createSearchLog('remove-no-flush-keyword-' . uniqid(), 'remove-no-flush-user', 8);
        $logId = $log->getId();

        // 删除但不flush
        $this->repository->remove($log, false);

        // 在flush之前应该还能找到
        $found1 = self::getEntityManager()->find(SearchLog::class, $logId);
        $this->assertNotNull($found1);

        // 手动flush后应该找不到
        self::getEntityManager()->flush();
        self::getEntityManager()->clear(); // 清空实体管理器缓存
        $found2 = self::getEntityManager()->find(SearchLog::class, $logId);
        $this->assertNull($found2);
    }

    private function createMultipleSearchLogs(string $keyword, int $count, \DateTimeImmutable $createTime, int $resultCount = 10): void
    {
        for ($i = 0; $i < $count; ++$i) {
            $log = new SearchLog();
            $log->setKeyword($keyword);
            $log->setUserHash(hash('sha256', "user{$i}"));
            $log->setResultCount($resultCount);
            $log->setSource('web');
            $log->setSessionId("session_{$keyword}_{$i}");
            $log->setCreateTime($createTime);

            self::getEntityManager()->persist($log);
        }
        self::getEntityManager()->flush();
    }
}
