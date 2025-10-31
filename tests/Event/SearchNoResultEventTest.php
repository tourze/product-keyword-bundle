<?php

namespace ProductKeywordBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use ProductKeywordBundle\Event\SearchNoResultEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * SearchNoResultEvent 测试
 * @internal
 */
#[CoversClass(SearchNoResultEvent::class)]
final class SearchNoResultEventTest extends AbstractEventTestCase
{
    public function testSearchNoResultEvent(): void
    {
        $event = new SearchNoResultEvent('未知产品', 'user123');

        $this->assertEquals('未知产品', $event->getKeyword());
        $this->assertEquals('user123', $event->getUserId());
    }
}
