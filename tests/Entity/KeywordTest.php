<?php

namespace ProductKeywordBundle\Tests\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use PHPUnit\Framework\Attributes\CoversClass;
use ProductKeywordBundle\Entity\Keyword;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * @internal
 */
#[CoversClass(Keyword::class)]
final class KeywordTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        $keyword = new Keyword();
        $keyword->setKeyword('test-keyword');
        $keyword->setWeight(1.0);
        $keyword->setValid(true);

        return $keyword;
    }

    /**
     * @return iterable<string, array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        yield 'valid' => ['valid', true];
        yield 'keyword' => ['keyword', 'test-keyword'];
        yield 'weight' => ['weight', 2.5];
        yield 'thumb' => ['thumb', 'test-thumb.jpg'];
        yield 'description' => ['description', 'test description'];
        yield 'recommend' => ['recommend', true];
    }

    public function testConstruct(): void
    {
        $keyword = new Keyword();

        $this->assertInstanceOf(ArrayCollection::class, $keyword->getProductKeywords());
        $this->assertFalse($keyword->isValid());
        $this->assertEquals(1.0, $keyword->getWeight());
        $this->assertFalse($keyword->isRecommend());
    }

    public function testSetGetKeyword(): void
    {
        $keyword = new Keyword();
        $testKeyword = 'test keyword';

        $keyword->setKeyword($testKeyword);

        $this->assertEquals($testKeyword, $keyword->getKeyword());
    }

    public function testSetGetWeight(): void
    {
        $keyword = new Keyword();
        $weight = 2.5;

        $keyword->setWeight($weight);

        $this->assertEquals($weight, $keyword->getWeight());
    }

    public function testSetGetValid(): void
    {
        $keyword = new Keyword();

        $keyword->setValid(true);

        $this->assertTrue($keyword->isValid());
    }

    public function testSetGetThumb(): void
    {
        $keyword = new Keyword();
        $thumb = 'thumbnail.jpg';

        $keyword->setThumb($thumb);

        $this->assertEquals($thumb, $keyword->getThumb());
    }

    public function testSetGetDescription(): void
    {
        $keyword = new Keyword();
        $description = 'Test description';

        $keyword->setDescription($description);

        $this->assertEquals($description, $keyword->getDescription());
    }

    public function testSetGetRecommend(): void
    {
        $keyword = new Keyword();

        $keyword->setRecommend(true);

        $this->assertTrue($keyword->isRecommend());
    }

    public function testSetGetParent(): void
    {
        $keyword = new Keyword();
        $parent = new Keyword();
        $parent->setKeyword('Parent Keyword');

        $keyword->setParent($parent);

        $this->assertSame($parent, $keyword->getParent());
    }

    public function testGetParentName(): void
    {
        $keyword = new Keyword();
        $parent = new Keyword();
        $parent->setKeyword('Parent Keyword');

        $keyword->setParent($parent);

        $this->assertEquals('Parent Keyword', $keyword->getParentName());
    }

    public function testGetParentNameWithoutParent(): void
    {
        $keyword = new Keyword();

        $this->assertNull($keyword->getParentName());
    }

    public function testRetrieveApiArray(): void
    {
        $keyword = new Keyword();
        $keyword->setKeyword('test');
        $keyword->setWeight(2.0);

        $result = $keyword->retrieveApiArray();

        $this->assertEquals('test', $result['keyword']);
        $this->assertEquals(2.0, $result['weight']);
        $this->assertArrayHasKey('id', $result);
    }

    public function testToString(): void
    {
        $keyword = new Keyword();
        $result = (string) $keyword;

        // 验证字符串转换方法存在，新实体返回空字符串
        $this->assertSame('', $result);
    }
}
