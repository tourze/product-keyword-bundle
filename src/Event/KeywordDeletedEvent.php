<?php

namespace ProductKeywordBundle\Event;

use ProductKeywordBundle\Entity\Keyword;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * 关键词删除事件
 */
class KeywordDeletedEvent extends Event
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
