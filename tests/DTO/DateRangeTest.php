<?php

namespace ProductKeywordBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ProductKeywordBundle\DTO\DateRange;
use ProductKeywordBundle\Exception\InvalidDateRangeException;

/**
 * DateRange 测试
 * @internal
 */
#[CoversClass(DateRange::class)]
final class DateRangeTest extends TestCase
{
    public function testConstructorWithValidRange(): void
    {
        $start = new \DateTimeImmutable('2024-01-01');
        $end = new \DateTimeImmutable('2024-01-31');

        $range = new DateRange($start, $end);

        $this->assertEquals($start, $range->getStart());
        $this->assertEquals($end, $range->getEnd());
    }

    public function testConstructorWithInvalidRange(): void
    {
        $start = new \DateTimeImmutable('2024-01-31');
        $end = new \DateTimeImmutable('2024-01-01');

        $this->expectException(InvalidDateRangeException::class);

        new DateRange($start, $end);
    }

    public function testGetDays(): void
    {
        $start = new \DateTimeImmutable('2024-01-01');
        $end = new \DateTimeImmutable('2024-01-05');

        $range = new DateRange($start, $end);

        $this->assertEquals(5, $range->getDays());
    }

    public function testGetDaysWithSameDate(): void
    {
        $date = new \DateTimeImmutable('2024-01-01');

        $range = new DateRange($date, $date);

        $this->assertEquals(1, $range->getDays());
    }

    public function testLastDays(): void
    {
        $range = DateRange::lastDays(7);

        $this->assertInstanceOf(DateRange::class, $range);
        $this->assertEquals(8, $range->getDays()); // 包含今天，所以是8天
    }

    public function testCurrentMonth(): void
    {
        $range = DateRange::currentMonth();

        $this->assertInstanceOf(DateRange::class, $range);

        $expectedStart = new \DateTimeImmutable('first day of this month');
        $expectedEnd = new \DateTimeImmutable('last day of this month');

        $this->assertEquals($expectedStart->format('Y-m-d'), $range->getStart()->format('Y-m-d'));
        $this->assertEquals($expectedEnd->format('Y-m-d'), $range->getEnd()->format('Y-m-d'));
    }
}
