<?php

namespace ProductKeywordBundle\DTO;

/**
 * 搜索日志查询条件
 */
class SearchLogCriteria
{
    public function __construct(
        public readonly ?string $keyword = null,
        public readonly ?string $userId = null,
        public readonly ?string $source = null,
        public readonly ?\DateTimeInterface $startDate = null,
        public readonly ?\DateTimeInterface $endDate = null,
        public readonly ?int $minResultCount = null,
        public readonly ?int $maxResultCount = null,
        public readonly int $page = 1,
        public readonly int $limit = 100,
        public readonly string $orderBy = 'createTime',
        public readonly string $orderDir = 'DESC',
    ) {
    }
}
