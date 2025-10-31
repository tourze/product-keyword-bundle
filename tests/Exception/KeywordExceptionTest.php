<?php

namespace ProductKeywordBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use ProductKeywordBundle\Exception\DuplicateKeywordException;
use ProductKeywordBundle\Exception\KeywordException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * KeywordException 测试
 * @internal
 */
#[CoversClass(KeywordException::class)]
final class KeywordExceptionTest extends AbstractExceptionTestCase
{
    public function testKeywordExceptionIsAbstractBase(): void
    {
        // 测试抽象基类通过其具体实现
        $exception = new DuplicateKeywordException('测试关键词');
        $this->assertInstanceOf(KeywordException::class, $exception);
        $this->assertInstanceOf(\Exception::class, $exception);
        $this->assertEquals('关键词"测试关键词"已存在', $exception->getMessage());
    }

    public function testKeywordExceptionIsAbstract(): void
    {
        $reflection = new \ReflectionClass(KeywordException::class);
        $this->assertTrue($reflection->isAbstract());
    }
}
