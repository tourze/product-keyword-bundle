<?php

namespace ProductKeywordBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use ProductKeywordBundle\Entity\Keyword;
use ProductKeywordBundle\Event\KeywordUpdatedEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * KeywordUpdatedEvent 测试
 * @internal
 */
#[CoversClass(KeywordUpdatedEvent::class)]
final class KeywordUpdatedEventTest extends AbstractEventTestCase
{
    public function testKeywordUpdatedEvent(): void
    {
        $keyword = new Keyword();
        $keyword->setKeyword('电脑');

        $event = new KeywordUpdatedEvent($keyword);

        $this->assertSame($keyword, $event->getKeyword());
    }
}
