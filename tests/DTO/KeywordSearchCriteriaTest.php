<?php

namespace ProductKeywordBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ProductKeywordBundle\DTO\KeywordSearchCriteria;

/**
 * KeywordSearchCriteria 测试
 * @internal
 */
#[CoversClass(KeywordSearchCriteria::class)]
final class KeywordSearchCriteriaTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $criteria = new KeywordSearchCriteria();

        $this->assertNull($criteria->keyword);
        $this->assertNull($criteria->parentId);
        $this->assertNull($criteria->valid);
        $this->assertNull($criteria->recommend);
        $this->assertNull($criteria->minWeight);
        $this->assertNull($criteria->maxWeight);
        $this->assertEquals(1, $criteria->page);
        $this->assertEquals(20, $criteria->limit);
        $this->assertEquals('id', $criteria->orderBy);
        $this->assertEquals('DESC', $criteria->orderDir);
    }

    public function testConstructorWithAllParameters(): void
    {
        $criteria = new KeywordSearchCriteria(
            keyword: '手机',
            parentId: 123,
            valid: true,
            recommend: false,
            minWeight: 1.0,
            maxWeight: 10.0,
            page: 2,
            limit: 50,
            orderBy: 'weight',
            orderDir: 'ASC'
        );

        $this->assertEquals('手机', $criteria->keyword);
        $this->assertEquals(123, $criteria->parentId);
        $this->assertTrue($criteria->valid);
        $this->assertFalse($criteria->recommend);
        $this->assertEquals(1.0, $criteria->minWeight);
        $this->assertEquals(10.0, $criteria->maxWeight);
        $this->assertEquals(2, $criteria->page);
        $this->assertEquals(50, $criteria->limit);
        $this->assertEquals('weight', $criteria->orderBy);
        $this->assertEquals('ASC', $criteria->orderDir);
    }

    public function testConstructorWithPartialParameters(): void
    {
        $criteria = new KeywordSearchCriteria(
            keyword: '电脑',
            valid: true,
            page: 3
        );

        $this->assertEquals('电脑', $criteria->keyword);
        $this->assertNull($criteria->parentId);
        $this->assertTrue($criteria->valid);
        $this->assertNull($criteria->recommend);
        $this->assertNull($criteria->minWeight);
        $this->assertNull($criteria->maxWeight);
        $this->assertEquals(3, $criteria->page);
        $this->assertEquals(20, $criteria->limit);
        $this->assertEquals('id', $criteria->orderBy);
        $this->assertEquals('DESC', $criteria->orderDir);
    }
}
