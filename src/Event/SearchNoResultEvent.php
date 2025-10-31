<?php

namespace ProductKeywordBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

/**
 * 搜索无结果事件
 */
class SearchNoResultEvent extends Event
{
    public function __construct(
        private readonly string $keyword,
        private readonly string $userId,
    ) {
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }

    public function getUserId(): string
    {
        return $this->userId;
    }
}
