<?php

namespace ProductKeywordBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ProductKeywordBundle\DTO\SearchLogDTO;

/**
 * SearchLogDTO 测试
 * @internal
 */
#[CoversClass(SearchLogDTO::class)]
final class SearchLogDTOTest extends TestCase
{
    public function testConstructorWithRequiredParameters(): void
    {
        $dto = new SearchLogDTO(
            keyword: '手机',
            userId: 'user123',
            resultCount: 100,
            source: 'mobile',
            sessionId: 'session456'
        );

        $this->assertEquals('手机', $dto->keyword);
        $this->assertEquals('user123', $dto->userId);
        $this->assertEquals(100, $dto->resultCount);
        $this->assertEquals('mobile', $dto->source);
        $this->assertEquals('session456', $dto->sessionId);
        $this->assertInstanceOf(\DateTimeImmutable::class, $dto->createTime);
    }

    public function testConstructorWithCustomCreateTime(): void
    {
        $createTime = new \DateTimeImmutable('2024-01-01 10:00:00');
        $dto = new SearchLogDTO(
            keyword: '电脑',
            userId: 'user456',
            resultCount: 50,
            source: 'pc',
            sessionId: 'session789',
            createTime: $createTime
        );

        $this->assertEquals($createTime, $dto->createTime);
    }

    public function testFromArray(): void
    {
        $data = [
            'keyword' => '平板',
            'userId' => 'user789',
            'resultCount' => 25,
            'source' => 'app',
            'sessionId' => 'session123',
        ];

        $dto = SearchLogDTO::fromArray($data);

        $this->assertEquals('平板', $dto->keyword);
        $this->assertEquals('user789', $dto->userId);
        $this->assertEquals(25, $dto->resultCount);
        $this->assertEquals('app', $dto->source);
        $this->assertEquals('session123', $dto->sessionId);
    }

    public function testToArray(): void
    {
        $createTime = new \DateTimeImmutable('2024-01-01 12:00:00');
        $dto = new SearchLogDTO(
            keyword: '键盘',
            userId: 'user999',
            resultCount: 75,
            source: 'mobile',
            sessionId: 'session999',
            createTime: $createTime
        );

        $array = $dto->toArray();

        $this->assertEquals('键盘', $array['keyword']);
        $this->assertEquals('user999', $array['userId']);
        $this->assertEquals(75, $array['resultCount']);
        $this->assertEquals('mobile', $array['source']);
        $this->assertEquals('session999', $array['sessionId']);
        $this->assertEquals('2024-01-01 12:00:00', $array['createTime']);
    }
}
