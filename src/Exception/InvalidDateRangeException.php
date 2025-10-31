<?php

namespace ProductKeywordBundle\Exception;

/**
 * 无效的日期范围异常
 */
class InvalidDateRangeException extends \InvalidArgumentException
{
    public static function startAfterEnd(): self
    {
        return new self('开始日期不能晚于结束日期');
    }
}
