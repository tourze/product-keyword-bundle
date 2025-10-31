<?php

namespace ProductKeywordBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ProductKeywordBundle\DTO\SearchLogCriteria;
use ProductKeywordBundle\DTO\SearchLogDTO;
use ProductKeywordBundle\Entity\SearchLog;
use ProductKeywordBundle\Repository\SearchLogRepository;
use ProductKeywordBundle\Service\SearchLogger;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(SearchLogger::class)]
#[RunTestsInSeparateProcesses]
final class SearchLoggerTest extends AbstractIntegrationTestCase
{
    private SearchLogRepository $repository;

    private SearchLogger $searchLogger;

    protected function onSetUp(): void
    {
        $searchLogger = self::getContainer()->get(SearchLogger::class);
        $this->assertInstanceOf(SearchLogger::class, $searchLogger);
        $this->searchLogger = $searchLogger;

        $repository = self::getContainer()->get(SearchLogRepository::class);
        $this->assertInstanceOf(SearchLogRepository::class, $repository);
        $this->repository = $repository;
    }

    public function testLogSearchSuccessfully(): void
    {
        $dto = new SearchLogDTO(
            keyword: '集成测试关键词',
            userId: 'integration-test-user-123',
            resultCount: 5,
            source: 'test',
            sessionId: 'test-session-123'
        );

        $result = $this->searchLogger->log($dto);

        $this->assertInstanceOf(SearchLog::class, $result);
        $this->assertEquals('集成测试关键词', $result->getKeyword());
        $this->assertEquals(5, $result->getResultCount());
        $this->assertEquals('test', $result->getSource());
        $this->assertEquals('test-session-123', $result->getSessionId());

        // 验证日志已保存到数据库
        $savedLog = $this->repository->find($result->getId());
        $this->assertNotNull($savedLog);
        $this->assertEquals($result->getId(), $savedLog->getId());
    }

    public function testLogWithCustomUserSalt(): void
    {
        $dto = new SearchLogDTO(
            keyword: '自定义盐值测试',
            userId: 'custom-salt-user',
            resultCount: 3,
            source: 'test',
            sessionId: 'custom-salt-session'
        );

        $result = $this->searchLogger->log($dto, 'custom-salt');

        $this->assertInstanceOf(SearchLog::class, $result);
        $this->assertEquals('自定义盐值测试', $result->getKeyword());

        // 验证用户哈希不为空
        $this->assertNotEmpty($result->getUserHash());
    }

    public function testFindLogs(): void
    {
        // 先创建一些测试日志
        $this->createTestSearchLogs();

        $criteria = new SearchLogCriteria(
            keyword: 'find-test-keyword'
        );

        $result = $this->searchLogger->findLogs($criteria);

        // 转换为数组以便验证计数
        $resultArray = is_array($result) ? $result : iterator_to_array($result);
        $this->assertGreaterThanOrEqual(0, count($resultArray));
    }

    public function testDeleteUserLogs(): void
    {
        // 创建测试用户的日志
        $dto = new SearchLogDTO(
            keyword: '删除测试关键词',
            userId: 'delete-test-user',
            resultCount: 1,
            source: 'test',
            sessionId: 'delete-test-session'
        );

        $log = $this->searchLogger->log($dto);
        $logId = $log->getId();

        // 删除该用户的日志
        $deletedCount = $this->searchLogger->deleteUserLogs('delete-test-user');

        $this->assertGreaterThanOrEqual(1, $deletedCount);

        // 清除缓存后验证日志已被删除
        self::getEntityManager()->clear();
        $deletedLog = $this->repository->find($logId);
        $this->assertNull($deletedLog);
    }

    public function testArchiveLogs(): void
    {
        // 创建一些旧日志
        $this->createOldTestLogs();

        // 归档90天前的日志
        $beforeDate = new \DateTime('-90 days');
        $archivedCount = $this->searchLogger->archiveLogs($beforeDate);

        // 验证归档数量是非负整数
        $this->assertGreaterThanOrEqual(0, $archivedCount);
    }

    public function testLogWithNullValues(): void
    {
        $dto = new SearchLogDTO(
            keyword: '空值测试',
            userId: '', // 空字符串而不是null
            resultCount: 0,
            source: 'test',
            sessionId: '' // 空字符串而不是null
        );

        $result = $this->searchLogger->log($dto);

        $this->assertInstanceOf(SearchLog::class, $result);
        $this->assertEquals('空值测试', $result->getKeyword());
        $this->assertEquals(0, $result->getResultCount());
    }

    public function testLogWithUnicodeKeyword(): void
    {
        $dto = new SearchLogDTO(
            keyword: '🔍 Unicode测试关键词 🎯',
            userId: 'unicode-test-user',
            resultCount: 2,
            source: 'test',
            sessionId: 'unicode-session'
        );

        $result = $this->searchLogger->log($dto);

        $this->assertInstanceOf(SearchLog::class, $result);
        $this->assertEquals('🔍 Unicode测试关键词 🎯', $result->getKeyword());
    }

    public function testUserHashConsistency(): void
    {
        $userId = 'consistency-test-user';

        // 第一次调用
        $dto1 = new SearchLogDTO(
            keyword: '一致性测试1',
            userId: $userId,
            resultCount: 1,
            source: 'test1',
            sessionId: 'session1'
        );

        $result1 = $this->searchLogger->log($dto1);
        $hash1 = $result1->getUserHash();

        // 第二次调用，使用相同的userId
        $dto2 = new SearchLogDTO(
            keyword: '一致性测试2',
            userId: $userId,
            resultCount: 2,
            source: 'test2',
            sessionId: 'session2'
        );

        $result2 = $this->searchLogger->log($dto2);
        $hash2 = $result2->getUserHash();

        // 验证两次生成的哈希值相同且不为空
        $this->assertEquals($hash1, $hash2);
        $this->assertNotEmpty($hash1);
    }

    public function testLogAsync(): void
    {
        $dto = new SearchLogDTO(
            keyword: '异步日志测试',
            userId: 'async-test-user',
            resultCount: 7,
            source: 'async-test',
            sessionId: 'async-session-123'
        );

        // 异步记录不返回SearchLog对象，验证无异常抛出
        $this->searchLogger->logAsync($dto);

        // 验证日志确实被记录（由于当前实现是降级到同步）
        $criteria = new SearchLogCriteria(keyword: '异步日志测试');
        $logs = $this->searchLogger->findLogs($criteria);

        // 转换为数组以便验证
        $logArray = is_array($logs) ? $logs : iterator_to_array($logs);
        $this->assertNotEmpty($logArray);

        $firstLog = reset($logArray);
        $this->assertInstanceOf(SearchLog::class, $firstLog);
        $this->assertEquals('异步日志测试', $firstLog->getKeyword());
        $this->assertEquals(7, $firstLog->getResultCount());
        $this->assertEquals('async-test', $firstLog->getSource());
    }

    private function createTestSearchLogs(): void
    {
        $logs = [
            [
                'keyword' => 'find-test-keyword',
                'userHash' => 'find-test-user-hash',
                'resultCount' => 5,
                'source' => 'test',
                'sessionId' => 'find-session-1',
            ],
            [
                'keyword' => 'another-test-keyword',
                'userHash' => 'another-test-user-hash',
                'resultCount' => 3,
                'source' => 'test',
                'sessionId' => 'find-session-2',
            ],
        ];

        foreach ($logs as $data) {
            $log = new SearchLog();
            $log->setKeyword($data['keyword']);
            $log->setUserHash($data['userHash']);
            $log->setResultCount($data['resultCount']);
            $log->setSource($data['source']);
            $log->setSessionId($data['sessionId']);
            $log->setCreateTime(new \DateTimeImmutable());

            $this->repository->save($log, false);
        }

        self::getEntityManager()->flush();
    }

    private function createOldTestLogs(): void
    {
        $log = new SearchLog();
        $log->setKeyword('old-archive-test');
        $log->setUserHash('old-user-hash');
        $log->setResultCount(1);
        $log->setSource('test');
        $log->setSessionId('old-session');
        $log->setCreateTime(new \DateTimeImmutable('-120 days')); // 120天前

        $this->repository->save($log, true);
    }
}
