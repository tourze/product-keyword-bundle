<?php

namespace ProductKeywordBundle\Tests\DTO;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use ProductKeywordBundle\DTO\KeywordDTO;

/**
 * KeywordDTO 测试
 * @internal
 */
#[CoversClass(KeywordDTO::class)]
final class KeywordDTOTest extends TestCase
{
    public function testConstructorWithRequiredParameters(): void
    {
        $dto = new KeywordDTO(
            keyword: '手机'
        );

        $this->assertEquals('手机', $dto->keyword);
        $this->assertEquals(1.0, $dto->weight);
        $this->assertNull($dto->parentId);
        $this->assertTrue($dto->valid);
        $this->assertFalse($dto->recommend);
        $this->assertNull($dto->description);
    }

    public function testConstructorWithAllParameters(): void
    {
        $dto = new KeywordDTO(
            keyword: '智能手机',
            weight: 10.0,
            parentId: '123',
            valid: true,
            recommend: true,
            description: '智能手机相关产品'
        );

        $this->assertEquals('智能手机', $dto->keyword);
        $this->assertEquals(10.0, $dto->weight);
        $this->assertEquals('123', $dto->parentId);
        $this->assertTrue($dto->valid);
        $this->assertTrue($dto->recommend);
        $this->assertEquals('智能手机相关产品', $dto->description);
    }

    public function testCreateFromArray(): void
    {
        $data = [
            'keyword' => '电脑',
            'weight' => 5.0,
            'parentId' => '456',
            'valid' => false,
            'recommend' => true,
            'description' => '电脑类产品',
        ];

        $dto = KeywordDTO::fromArray($data);

        $this->assertEquals('电脑', $dto->keyword);
        $this->assertEquals(5.0, $dto->weight);
        $this->assertEquals('456', $dto->parentId);
        $this->assertFalse($dto->valid);
        $this->assertTrue($dto->recommend);
        $this->assertEquals('电脑类产品', $dto->description);
    }

    public function testToArray(): void
    {
        $dto = new KeywordDTO(
            keyword: '平板',
            weight: 3.0,
            parentId: '789',
            valid: true,
            recommend: false,
            description: '平板电脑'
        );

        $array = $dto->toArray();

        $this->assertEquals([
            'keyword' => '平板',
            'weight' => 3.0,
            'parentId' => '789',
            'valid' => true,
            'recommend' => false,
            'description' => '平板电脑',
        ], $array);
    }
}
