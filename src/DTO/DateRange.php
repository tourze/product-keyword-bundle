<?php

namespace ProductKeywordBundle\DTO;

use ProductKeywordBundle\Exception\InvalidDateRangeException;

/**
 * 日期范围
 */
class DateRange
{
    public function __construct(
        private readonly \DateTimeInterface $start,
        private readonly \DateTimeInterface $end,
    ) {
        if ($start > $end) {
            throw InvalidDateRangeException::startAfterEnd();
        }
    }

    public function getStart(): \DateTimeInterface
    {
        return $this->start;
    }

    public function getEnd(): \DateTimeInterface
    {
        return $this->end;
    }

    /**
     * 获取天数
     */
    public function getDays(): int
    {
        $diff = $this->start->diff($this->end);

        return (false === $diff->days ? 0 : $diff->days) + 1;
    }

    /**
     * 创建最近N天的范围
     */
    public static function lastDays(int $days): self
    {
        return new self(
            new \DateTimeImmutable("-{$days} days"),
            new \DateTimeImmutable()
        );
    }

    /**
     * 创建本月范围
     */
    public static function currentMonth(): self
    {
        return new self(
            new \DateTimeImmutable('first day of this month'),
            new \DateTimeImmutable('last day of this month')
        );
    }
}
