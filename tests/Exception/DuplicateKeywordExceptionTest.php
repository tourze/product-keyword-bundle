<?php

namespace ProductKeywordBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use ProductKeywordBundle\Exception\DuplicateKeywordException;
use ProductKeywordBundle\Exception\KeywordException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * DuplicateKeywordException 测试
 * @internal
 */
#[CoversClass(DuplicateKeywordException::class)]
final class DuplicateKeywordExceptionTest extends AbstractExceptionTestCase
{
    public function testDuplicateKeywordExceptionExtendsBase(): void
    {
        $exception = new DuplicateKeywordException('手机');
        $this->assertInstanceOf(KeywordException::class, $exception);
        $this->assertEquals('关键词"手机"已存在', $exception->getMessage());
    }

    public function testDuplicateKeywordExceptionWithKeyword(): void
    {
        $exception = DuplicateKeywordException::forKeyword('电脑');
        $this->assertEquals('关键词"电脑"已存在', $exception->getMessage());
    }
}
