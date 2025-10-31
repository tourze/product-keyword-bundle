<?php

namespace ProductKeywordBundle\Event;

use ProductKeywordBundle\Entity\Keyword;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * 关键词创建事件
 */
class KeywordCreatedEvent extends Event
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
