<?php

namespace ProductKeywordBundle\Interface;

use ProductKeywordBundle\DTO\DateRange;

/**
 * 搜索分析接口
 */
interface SearchAnalyzerInterface
{
    /**
     * 分析热门关键词
     * @return array<array{keyword: string, count: int, trend: float}>
     */
    public function analyzeHotKeywords(DateRange $range, int $limit = 100): array;

    /**
     * 分析搜索命中率
     * @return array{total_searches: int, no_result_searches: int, hit_rate: float}
     */
    public function analyzeHitRate(DateRange $range): array;

    /**
     * 分析搜索转化率
     * @return array<array{keyword: string, searches: int, clicks: int, conversions: int, rate: float}>
     */
    public function analyzeConversion(DateRange $range): array;

    /**
     * 分析关键词趋势
     * @return array<array{date: string, count: int}>
     */
    public function analyzeTrends(string $keyword, DateRange $range): array;

    /**
     * 识别无结果搜索词
     * @return array<array{keyword: string, count: int}>
     */
    public function findNoResultKeywords(DateRange $range, int $limit = 100): array;
}
