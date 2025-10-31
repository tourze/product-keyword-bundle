<?php

namespace ProductKeywordBundle\Interface;

use ProductKeywordBundle\DTO\DateRange;

/**
 * 搜索优化接口
 */
interface SearchOptimizerInterface
{
    /**
     * 推荐相关关键词
     * @return array<string>
     */
    public function recommend(string $query, int $limit = 10): array;

    /**
     * 纠正拼写错误
     */
    public function correct(string $query): ?string;

    /**
     * 获取同义词
     * @return array<string>
     */
    public function getSynonyms(string $keyword): array;

    /**
     * 自动提取关键词
     * @return array<array{keyword: string, frequency: int}>
     */
    public function extractKeywords(DateRange $range, int $limit = 100): array;

    /**
     * 优化关键词权重
     */
    public function optimizeWeights(string $strategy = 'conversion'): void;
}
