<?php

namespace ProductKeywordBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use ProductKeywordBundle\Exception\InvalidDateRangeException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * InvalidDateRangeException 测试
 * @internal
 */
#[CoversClass(InvalidDateRangeException::class)]
final class InvalidDateRangeExceptionTest extends AbstractExceptionTestCase
{
    public function testStartAfterEnd(): void
    {
        $exception = InvalidDateRangeException::startAfterEnd();

        $this->assertInstanceOf(InvalidDateRangeException::class, $exception);
        $this->assertInstanceOf(\InvalidArgumentException::class, $exception);
        $this->assertEquals('开始日期不能晚于结束日期', $exception->getMessage());
    }

    public function testExceptionCanBeThrown(): void
    {
        $this->expectException(InvalidDateRangeException::class);
        $this->expectExceptionMessage('开始日期不能晚于结束日期');

        throw InvalidDateRangeException::startAfterEnd();
    }
}
