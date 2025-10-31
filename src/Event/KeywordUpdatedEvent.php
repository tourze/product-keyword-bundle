<?php

namespace ProductKeywordBundle\Event;

use ProductKeywordBundle\Entity\Keyword;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * 关键词更新事件
 */
class KeywordUpdatedEvent extends Event
{
    public function __construct(
        private readonly Keyword $keyword,
    ) {
    }

    public function getKeyword(): Keyword
    {
        return $this->keyword;
    }
}
