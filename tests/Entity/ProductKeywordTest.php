<?php

namespace ProductKeywordBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use ProductKeywordBundle\Entity\Keyword;
use ProductKeywordBundle\Entity\ProductKeyword;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(ProductKeyword::class)]
final class ProductKeywordTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $keyword = new Keyword();
        $keyword->setKeyword('test-keyword');
        $keyword->setWeight(1.0);
        $keyword->setValid(true);

        $productKeyword = new ProductKeyword();
        $productKeyword->setSpuId('test-spu');
        $productKeyword->setKeyword($keyword);
        $productKeyword->setWeight(1.0);
        $productKeyword->setSource('manual');

        return $productKeyword;
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        $keyword = new Keyword();
        $keyword->setKeyword('test-keyword');
        $keyword->setWeight(1.0);
        $keyword->setValid(true);

        yield 'spuId' => ['spuId', 'test-spu-123'];
        yield 'weight' => ['weight', 2.5];
        yield 'source' => ['source', 'auto'];
    }

    public function testSetGetSpuId(): void
    {
        $productKeyword = new ProductKeyword();
        $spuId = '123456';

        $productKeyword->setSpuId($spuId);

        $this->assertEquals($spuId, $productKeyword->getSpuId());
    }

    public function testSetGetKeyword(): void
    {
        $productKeyword = new ProductKeyword();
        $keyword = new Keyword();

        $productKeyword->setKeyword($keyword);

        $this->assertSame($keyword, $productKeyword->getKeyword());
    }

    public function testSetGetWeight(): void
    {
        $productKeyword = new ProductKeyword();
        $weight = 2.5;

        $productKeyword->setWeight($weight);

        $this->assertEquals($weight, $productKeyword->getWeight());
    }

    public function testSetGetSource(): void
    {
        $productKeyword = new ProductKeyword();
        $source = 'auto';

        $productKeyword->setSource($source);

        $this->assertEquals($source, $productKeyword->getSource());
    }

    public function testDefaultValues(): void
    {
        $productKeyword = new ProductKeyword();

        $this->assertEquals(1.0, $productKeyword->getWeight());
        $this->assertEquals('manual', $productKeyword->getSource());
    }

    public function testRetrieveApiArray(): void
    {
        $keyword = new Keyword();
        $keyword->setKeyword('test');
        $productKeyword = new ProductKeyword();
        $productKeyword->setSpuId('123');
        $productKeyword->setWeight(2.0);
        $productKeyword->setKeyword($keyword);

        $result = $productKeyword->retrieveApiArray();

        $this->assertEquals('123', $result['spuId']);
        $this->assertEquals(2.0, $result['weight']);
        $this->assertArrayHasKey('id', $result);
    }

    public function testToString(): void
    {
        $productKeyword = new ProductKeyword();
        $result = (string) $productKeyword;

        // 验证字符串转换方法存在，新实体返回ID字符串（Snowflake默认为0）
        $this->assertSame('0', $result);
    }

    public function testGetKeywordWeight(): void
    {
        $keyword = new Keyword();
        $keyword->setWeight(3.5);

        $productKeyword = new ProductKeyword();
        $productKeyword->setKeyword($keyword);

        $this->assertEquals(3.5, $productKeyword->getKeywordWeight());
    }
}
