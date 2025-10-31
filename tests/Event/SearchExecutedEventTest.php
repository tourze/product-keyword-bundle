<?php

namespace ProductKeywordBundle\Tests\Event;

use PHPUnit\Framework\Attributes\CoversClass;
use ProductKeywordBundle\Event\SearchExecutedEvent;
use Tourze\PHPUnitSymfonyUnitTest\AbstractEventTestCase;

/**
 * SearchExecutedEvent 测试
 * @internal
 */
#[CoversClass(SearchExecutedEvent::class)]
final class SearchExecutedEventTest extends AbstractEventTestCase
{
    public function testSearchExecutedEvent(): void
    {
        $event = new SearchExecutedEvent('手机', 150);

        $this->assertEquals('手机', $event->getKeyword());
        $this->assertEquals(150, $event->getResultCount());
    }

    public function testSearchExecutedEventWithOptionalData(): void
    {
        $searchData = [
            'filters' => ['category' => 'electronics'],
            'sort' => 'price',
            'page' => 1,
        ];

        $event = new SearchExecutedEvent('手机', 150, $searchData);

        $this->assertEquals($searchData, $event->getSearchData());
    }
}
