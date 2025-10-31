<?php

namespace ProductKeywordBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ProductKeywordBundle\DTO\SearchLogCriteria;

/**
 * SearchLogCriteria 测试
 * @internal
 */
#[CoversClass(SearchLogCriteria::class)]
final class SearchLogCriteriaTest extends TestCase
{
    public function testConstructorWithDefaults(): void
    {
        $criteria = new SearchLogCriteria();

        $this->assertNull($criteria->keyword);
        $this->assertNull($criteria->userId);
        $this->assertNull($criteria->source);
        $this->assertNull($criteria->startDate);
        $this->assertNull($criteria->endDate);
        $this->assertNull($criteria->minResultCount);
        $this->assertNull($criteria->maxResultCount);
        $this->assertEquals(1, $criteria->page);
        $this->assertEquals(100, $criteria->limit);
        $this->assertEquals('createTime', $criteria->orderBy);
        $this->assertEquals('DESC', $criteria->orderDir);
    }

    public function testConstructorWithAllParameters(): void
    {
        $startDate = new \DateTimeImmutable('2024-01-01');
        $endDate = new \DateTimeImmutable('2024-01-31');

        $criteria = new SearchLogCriteria(
            keyword: '手机',
            userId: 'user123',
            source: 'web',
            startDate: $startDate,
            endDate: $endDate,
            minResultCount: 1,
            maxResultCount: 100,
            page: 2,
            limit: 50,
            orderBy: 'keyword',
            orderDir: 'ASC'
        );

        $this->assertEquals('手机', $criteria->keyword);
        $this->assertEquals('user123', $criteria->userId);
        $this->assertEquals('web', $criteria->source);
        $this->assertEquals($startDate, $criteria->startDate);
        $this->assertEquals($endDate, $criteria->endDate);
        $this->assertEquals(1, $criteria->minResultCount);
        $this->assertEquals(100, $criteria->maxResultCount);
        $this->assertEquals(2, $criteria->page);
        $this->assertEquals(50, $criteria->limit);
        $this->assertEquals('keyword', $criteria->orderBy);
        $this->assertEquals('ASC', $criteria->orderDir);
    }

    public function testConstructorWithPartialParameters(): void
    {
        $criteria = new SearchLogCriteria(
            keyword: '电脑',
            userId: 'user456',
            page: 3
        );

        $this->assertEquals('电脑', $criteria->keyword);
        $this->assertEquals('user456', $criteria->userId);
        $this->assertNull($criteria->source);
        $this->assertNull($criteria->startDate);
        $this->assertNull($criteria->endDate);
        $this->assertNull($criteria->minResultCount);
        $this->assertNull($criteria->maxResultCount);
        $this->assertEquals(3, $criteria->page);
        $this->assertEquals(100, $criteria->limit);
        $this->assertEquals('createTime', $criteria->orderBy);
        $this->assertEquals('DESC', $criteria->orderDir);
    }
}
