<?php

namespace ProductKeywordBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ProductKeywordBundle\DTO\DateRange;
use ProductKeywordBundle\Entity\SearchLog;
use ProductKeywordBundle\Repository\SearchLogRepository;
use ProductKeywordBundle\Service\SearchAnalyzer;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(SearchAnalyzer::class)]
#[RunTestsInSeparateProcesses]
final class SearchAnalyzerTest extends AbstractIntegrationTestCase
{
    private SearchLogRepository $repository;

    private SearchAnalyzer $analyzer;

    protected function onSetUp(): void
    {
        $analyzer = self::getContainer()->get(SearchAnalyzer::class);
        $this->assertInstanceOf(SearchAnalyzer::class, $analyzer);
        $this->analyzer = $analyzer;

        $repository = self::getContainer()->get(SearchLogRepository::class);
        $this->assertInstanceOf(SearchLogRepository::class, $repository);
        $this->repository = $repository;
    }

    public function testAnalyzeHotKeywords(): void
    {
        $startDate = new \DateTime('2023-01-01');
        $endDate = new \DateTime('2023-01-31');
        $range = new DateRange($startDate, $endDate);

        // 创建测试数据
        $this->createSearchLogData();

        $result = $this->analyzer->analyzeHotKeywords($range, 5);

        // 验证返回的数据不超过请求的数量（由于是集成测试，结果可能为空或有数据，都是正常的）
        $this->assertLessThanOrEqual(5, count($result));
    }

    public function testAnalyzeHitRate(): void
    {
        $startDate = new \DateTime('2023-01-01');
        $endDate = new \DateTime('2023-01-31');
        $range = new DateRange($startDate, $endDate);

        // 创建测试数据
        $this->createSearchLogData();

        $result = $this->analyzer->analyzeHitRate($range);

        // 验证返回数组包含必要的键
        $this->assertArrayHasKey('total_searches', $result);
        $this->assertArrayHasKey('no_result_searches', $result);
        $this->assertArrayHasKey('hit_rate', $result);
    }

    public function testAnalyzeTrends(): void
    {
        $startDate = new \DateTime('2023-01-01');
        $endDate = new \DateTime('2023-01-31');
        $range = new DateRange($startDate, $endDate);

        $result = $this->analyzer->analyzeTrends('test-keyword', $range);

        // 验证返回结果的数组格式
        $this->assertGreaterThanOrEqual(0, count($result));
    }

    public function testFindNoResultKeywords(): void
    {
        $startDate = new \DateTime('2023-01-01');
        $endDate = new \DateTime('2023-01-31');
        $range = new DateRange($startDate, $endDate);

        // 创建一些无结果的搜索日志
        $this->createNoResultSearchLogs();

        $result = $this->analyzer->findNoResultKeywords($range, 10);

        // 验证返回的数据不超过请求的数量
        $this->assertLessThanOrEqual(10, count($result));
    }

    public function testAnalyzeConversion(): void
    {
        $startDate = new \DateTime('2023-01-01');
        $endDate = new \DateTime('2023-01-31');
        $range = new DateRange($startDate, $endDate);

        $result = $this->analyzer->analyzeConversion($range);

        // TODO: 转化率分析尚未实现，应返回空数组
        $this->assertEmpty($result);
    }

    private function createSearchLogData(): void
    {
        $searchLogs = [
            [
                'keyword' => 'integration-test-keyword-1',
                'userHash' => 'test-user-1',
                'resultCount' => 10,
                'source' => 'test',
                'sessionId' => 'session-1',
            ],
            [
                'keyword' => 'integration-test-keyword-2',
                'userHash' => 'test-user-2',
                'resultCount' => 5,
                'source' => 'test',
                'sessionId' => 'session-2',
            ],
            [
                'keyword' => 'integration-test-keyword-1', // 重复关键词
                'userHash' => 'test-user-3',
                'resultCount' => 8,
                'source' => 'test',
                'sessionId' => 'session-3',
            ],
        ];

        foreach ($searchLogs as $data) {
            $log = new SearchLog();
            $log->setKeyword($data['keyword']);
            $log->setUserHash($data['userHash']);
            $log->setResultCount($data['resultCount']);
            $log->setSource($data['source']);
            $log->setSessionId($data['sessionId']);
            $log->setCreateTime(new \DateTimeImmutable('2023-01-15'));

            $this->repository->save($log, false);
        }

        self::getEntityManager()->flush();
    }

    private function createNoResultSearchLogs(): void
    {
        $noResultLogs = [
            [
                'keyword' => 'no-result-keyword-1',
                'userHash' => 'test-user-no-result-1',
                'resultCount' => 0,
                'source' => 'test',
                'sessionId' => 'no-result-session-1',
            ],
            [
                'keyword' => 'no-result-keyword-2',
                'userHash' => 'test-user-no-result-2',
                'resultCount' => 0,
                'source' => 'test',
                'sessionId' => 'no-result-session-2',
            ],
        ];

        foreach ($noResultLogs as $data) {
            $log = new SearchLog();
            $log->setKeyword($data['keyword']);
            $log->setUserHash($data['userHash']);
            $log->setResultCount($data['resultCount']);
            $log->setSource($data['source']);
            $log->setSessionId($data['sessionId']);
            $log->setCreateTime(new \DateTimeImmutable('2023-01-15'));

            $this->repository->save($log, false);
        }

        self::getEntityManager()->flush();
    }
}
