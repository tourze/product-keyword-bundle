<?php

namespace ProductKeywordBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use ProductKeywordBundle\Exception\KeywordException;
use ProductKeywordBundle\Exception\KeywordNotFoundException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * KeywordNotFoundException 测试
 * @internal
 */
#[CoversClass(KeywordNotFoundException::class)]
final class KeywordNotFoundExceptionTest extends AbstractExceptionTestCase
{
    public function testKeywordNotFoundExceptionExtendsBase(): void
    {
        $exception = new KeywordNotFoundException('123');
        $this->assertInstanceOf(KeywordException::class, $exception);
        $this->assertEquals('关键词ID 123 不存在', $exception->getMessage());
    }

    public function testKeywordNotFoundExceptionWithId(): void
    {
        $exception = KeywordNotFoundException::forId('456');
        $this->assertEquals('关键词ID 456 不存在', $exception->getMessage());
    }

    public function testKeywordNotFoundExceptionWithKeyword(): void
    {
        $exception = KeywordNotFoundException::forKeyword('未知关键词');
        $this->assertEquals('关键词ID 未知关键词 不存在', $exception->getMessage());
    }
}
