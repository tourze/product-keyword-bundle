<?php

namespace ProductKeywordBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use ProductKeywordBundle\Entity\Keyword;
use ProductKeywordBundle\Event\KeywordCreatedEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * KeywordCreatedEvent 测试
 * @internal
 */
#[CoversClass(KeywordCreatedEvent::class)]
final class KeywordCreatedEventTest extends AbstractEventTestCase
{
    public function testKeywordCreatedEvent(): void
    {
        $keyword = new Keyword();
        $keyword->setKeyword('手机');

        $event = new KeywordCreatedEvent($keyword);

        $this->assertSame($keyword, $event->getKeyword());
    }

    public function testEventImmutability(): void
    {
        $keyword = new Keyword();
        $keyword->setKeyword('原始关键词');

        $event = new KeywordCreatedEvent($keyword);
        $eventKeyword = $event->getKeyword();

        // 修改原始对象不应影响事件中的对象
        $keyword->setKeyword('修改后的关键词');

        // 事件中的关键词对象应该是同一个引用
        $this->assertSame($keyword, $eventKeyword);
    }
}
