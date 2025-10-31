<?php

namespace ProductKeywordBundle\Tests\Service;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ProductKeywordBundle\DTO\DateRange;
use ProductKeywordBundle\Service\SearchOptimizer;
use Tourze\PHPUnitSymfonyKernelTest\AbstractIntegrationTestCase;

/**
 * @internal
 */
#[CoversClass(SearchOptimizer::class)]
#[RunTestsInSeparateProcesses]
final class SearchOptimizerTest extends AbstractIntegrationTestCase
{
    private SearchOptimizer $optimizer;

    protected function onSetUp(): void
    {
        $optimizer = self::getContainer()->get(SearchOptimizer::class);
        $this->assertInstanceOf(SearchOptimizer::class, $optimizer);
        $this->optimizer = $optimizer;
    }

    public function testRecommendReturnsEmptyArray(): void
    {
        $result = $this->optimizer->recommend('不存在的关键词');

        // 验证返回空数组
        $this->assertEmpty($result);
    }

    public function testCorrectReturnsNull(): void
    {
        $result = $this->optimizer->correct('拼写错误');

        $this->assertNull($result);
    }

    public function testGetSynonymsReturnsEmptyArray(): void
    {
        $result = $this->optimizer->getSynonyms('关键词');

        // 验证返回空数组
        $this->assertEmpty($result);
    }

    public function testExtractKeywordsReturnsEmptyArray(): void
    {
        $startDate = new \DateTime('2023-01-01');
        $endDate = new \DateTime('2023-01-31');
        $range = new DateRange($startDate, $endDate);

        $result = $this->optimizer->extractKeywords($range);

        // 由于没有真实数据，应该返回空数组
        $this->assertEmpty($result);
    }

    public function testOptimizeWeightsDoesNotThrowException(): void
    {
        // 验证方法调用不会抛出异常
        $this->optimizer->optimizeWeights();
        $this->optimizer->optimizeWeights('conversion');
        $this->optimizer->optimizeWeights('frequency');

        // 验证方法执行完成
        $this->assertSame(1, 1);
    }
}
