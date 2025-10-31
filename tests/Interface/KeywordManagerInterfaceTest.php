<?php

namespace ProductKeywordBundle\Tests\Interface;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ProductKeywordBundle\DTO\KeywordDTO;
use ProductKeywordBundle\Entity\Keyword;
use ProductKeywordBundle\Interface\KeywordManagerInterface;

/**
 * KeywordManagerInterface 测试
 * @internal
 */
#[CoversClass(KeywordManagerInterface::class)]
final class KeywordManagerInterfaceTest extends TestCase
{
    public function testInterfaceDefinesRequiredMethods(): void
    {
        $reflection = new \ReflectionClass(KeywordManagerInterface::class);

        // 验证接口定义了所有必需的方法
        $this->assertTrue($reflection->hasMethod('create'));
        $this->assertTrue($reflection->hasMethod('update'));
        $this->assertTrue($reflection->hasMethod('delete'));
        $this->assertTrue($reflection->hasMethod('find'));
        $this->assertTrue($reflection->hasMethod('findByKeyword'));
        $this->assertTrue($reflection->hasMethod('search'));
        $this->assertTrue($reflection->hasMethod('attachToProduct'));
        $this->assertTrue($reflection->hasMethod('detachFromProduct'));
        $this->assertTrue($reflection->hasMethod('batchUpdateStatus'));
    }

    public function testCreateMethodSignature(): void
    {
        $reflection = new \ReflectionClass(KeywordManagerInterface::class);
        $method = $reflection->getMethod('create');

        // 验证参数
        $params = $method->getParameters();
        $this->assertCount(1, $params);
        $this->assertEquals('dto', $params[0]->getName());

        // 验证返回类型
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('ProductKeywordBundle\Entity\Keyword', (string) $returnType);
    }

    public function testAttachToProductMethodSignature(): void
    {
        $reflection = new \ReflectionClass(KeywordManagerInterface::class);
        $method = $reflection->getMethod('attachToProduct');

        // 验证参数
        $params = $method->getParameters();
        $this->assertGreaterThanOrEqual(2, count($params));
        $this->assertEquals('spuId', $params[0]->getName());
        $this->assertEquals('keywordId', $params[1]->getName());

        // 验证返回类型
        $returnType = $method->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('ProductKeywordBundle\Entity\ProductKeyword', (string) $returnType);
    }

    public function testMockImplementation(): void
    {
        // 创建 mock 实现验证接口可用性
        $mock = $this->createMock(KeywordManagerInterface::class);

        $keyword = new Keyword();
        $keyword->setKeyword('测试');

        $mock->expects($this->once())
            ->method('create')
            ->willReturn($keyword)
        ;

        $result = $mock->create(new KeywordDTO('测试'));
        $this->assertSame($keyword, $result);
    }
}
