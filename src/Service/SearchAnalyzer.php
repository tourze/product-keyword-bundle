<?php

namespace ProductKeywordBundle\Service;

use ProductKeywordBundle\DTO\DateRange;
use ProductKeywordBundle\Interface\SearchAnalyzerInterface;
use ProductKeywordBundle\Repository\SearchLogRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * 搜索分析服务
 */
#[Autoconfigure(public: true)]
readonly class SearchAnalyzer implements SearchAnalyzerInterface
{
    public function __construct(
        private SearchLogRepository $repository,
    ) {
    }

    public function analyzeHotKeywords(DateRange $range, int $limit = 100): array
    {
        $keywords = $this->repository->getHotKeywords(
            $range->getStart(),
            $range->getEnd(),
            $limit
        );

        // 为每个关键词添加趋势值（这里简单设为0，实际应计算趋势）
        return array_map(function (array $keyword) {
            $keyword['trend'] = 0.0;

            return $keyword;
        }, $keywords);
    }

    public function analyzeHitRate(DateRange $range): array
    {
        $total = $this->repository->countByDateRange($range->getStart(), $range->getEnd());
        $noResult = count($this->repository->getNoResultKeywords($range->getStart(), $range->getEnd()));

        return [
            'total_searches' => $total,
            'no_result_searches' => $noResult,
            'hit_rate' => $total > 0 ? ($total - $noResult) / $total : 0,
        ];
    }

    public function analyzeConversion(DateRange $range): array
    {
        // TODO: 实现转化率分析
        return [];
    }

    public function analyzeTrends(string $keyword, DateRange $range): array
    {
        // TODO: 实现趋势分析
        return [];
    }

    public function findNoResultKeywords(DateRange $range, int $limit = 100): array
    {
        return $this->repository->getNoResultKeywords(
            $range->getStart(),
            $range->getEnd(),
            $limit
        );
    }
}
