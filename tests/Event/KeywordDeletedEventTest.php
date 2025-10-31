<?php

namespace ProductKeywordBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use ProductKeywordBundle\Entity\Keyword;
use ProductKeywordBundle\Event\KeywordDeletedEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * KeywordDeletedEvent 测试
 * @internal
 */
#[CoversClass(KeywordDeletedEvent::class)]
final class KeywordDeletedEventTest extends AbstractEventTestCase
{
    public function testKeywordDeletedEvent(): void
    {
        $keyword = new Keyword();
        $keyword->setId('123');
        $keyword->setKeyword('平板');

        $event = new KeywordDeletedEvent($keyword);

        $this->assertSame($keyword, $event->getKeyword());
    }
}
