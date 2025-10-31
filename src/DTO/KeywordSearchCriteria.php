<?php

namespace ProductKeywordBundle\DTO;

/**
 * 关键词搜索条件
 */
class KeywordSearchCriteria
{
    public function __construct(
        public readonly ?string $keyword = null,
        public readonly ?int $parentId = null,
        public readonly ?bool $valid = null,
        public readonly ?bool $recommend = null,
        public readonly ?float $minWeight = null,
        public readonly ?float $maxWeight = null,
        public readonly int $page = 1,
        public readonly int $limit = 20,
        public readonly string $orderBy = 'id',
        public readonly string $orderDir = 'DESC',
    ) {
    }
}
