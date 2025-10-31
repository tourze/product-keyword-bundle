<?php

namespace ProductKeywordBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * 搜索执行事件
 */
class SearchExecutedEvent extends Event
{
    /**
     * @param array<string, mixed> $searchData
     */
    public function __construct(
        private readonly string $keyword,
        private readonly int $resultCount,
        private readonly array $searchData = [],
    ) {
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }

    public function getResultCount(): int
    {
        return $this->resultCount;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSearchData(): array
    {
        return $this->searchData;
    }
}
