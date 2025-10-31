<?php

namespace ProductKeywordBundle\Tests\Exception;

use PHPUnit\Framework\Attributes\CoversClass;
use ProductKeywordBundle\Exception\InvalidKeywordException;
use ProductKeywordBundle\Exception\KeywordException;
use Tourze\PHPUnitBase\AbstractExceptionTestCase;

/**
 * InvalidKeywordException 测试
 * @internal
 */
#[CoversClass(InvalidKeywordException::class)]
final class InvalidKeywordExceptionTest extends AbstractExceptionTestCase
{
    public function testInvalidKeywordExceptionExtendsBase(): void
    {
        $exception = new InvalidKeywordException('关键词长度必须在1-100之间');
        $this->assertInstanceOf(KeywordException::class, $exception);
        $this->assertEquals('关键词长度必须在1-100之间', $exception->getMessage());
    }

    public function testInvalidKeywordExceptionForLength(): void
    {
        $exception = InvalidKeywordException::forLength('test', 1, 100);
        $this->assertStringContainsString('长度必须在1-100之间', $exception->getMessage());
    }

    public function testInvalidKeywordExceptionForCharacters(): void
    {
        $exception = InvalidKeywordException::forInvalidCharacters('<script>');
        $this->assertStringContainsString('包含非法字符', $exception->getMessage());
    }
}
