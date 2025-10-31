<?php

declare(strict_types=1);

namespace ProductKeywordBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ProductKeywordBundle\Repository\ProductKeywordRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\Arrayable\PlainArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

/**
 * 商品关键词关联表
 *
 * @implements PlainArrayInterface<string, mixed>
 * @implements ApiArrayInterface<string, mixed>
 * @implements AdminArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: ProductKeywordRepository::class)]
#[ORM\Table(name: 'product_keyword_relation', options: ['comment' => '商品关键词关联表'])]
#[ORM\UniqueConstraint(name: 'product_keyword_relation_unique', columns: ['spu_id', 'keyword_id'])]
class ProductKeyword implements PlainArrayInterface, ApiArrayInterface, AdminArrayInterface, \Stringable
{
    use TimestampableAware;
    use BlameableAware;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => 'ID'])]
    private int $id = 0;

    #[ORM\Column(type: Types::STRING, length: 50, options: ['comment' => '商品ID'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 50)]
    private string $spuId;

    #[ORM\ManyToOne(targetEntity: Keyword::class, inversedBy: 'productKeywords', cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'keyword_id', referencedColumnName: 'id', onDelete: 'CASCADE', options: ['comment' => '关键词ID'])]
    private Keyword $keyword;

    #[ORM\Column(type: Types::FLOAT, options: ['default' => 1.0, 'comment' => '权重值'])]
    #[Assert\Range(min: 0, max: 10)]
    #[IndexColumn]
    private float $weight = 1.0;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['comment' => '来源', 'default' => 'manual'])]
    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[Assert\Choice(choices: ['manual', 'auto', 'import'])]
    private string $source = 'manual';

    /**
     * @return array<string, mixed>
     */
    public function retrieveApiArray(): array
    {
        return [
            'id' => $this->getId(),
            'spuId' => $this->getSpuId(),
            'keyword' => $this->getKeyword()->retrieveApiArray(),
            'weight' => $this->getWeight(),
        ];
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSpuId(): string
    {
        return $this->spuId;
    }

    public function setSpuId(string $spuId): void
    {
        $this->spuId = $spuId;
    }

    public function getKeyword(): Keyword
    {
        return $this->keyword;
    }

    public function setKeyword(Keyword $keyword): void
    {
        $this->keyword = $keyword;
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function setWeight(float $weight): void
    {
        $this->weight = $weight;
    }

    /**
     * 获取关联关键词的权重值
     * 用于 EasyAdmin 字段显示
     */
    public function getKeywordWeight(): float
    {
        return $this->keyword->getWeight();
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveAdminArray(): array
    {
        return $this->retrievePlainArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function retrievePlainArray(): array
    {
        return [
            'id' => $this->getId(),
            'spuId' => $this->getSpuId(),
            'keyword' => $this->getKeyword()->retrievePlainArray(),
            'weight' => $this->getWeight(),
            'source' => $this->getSource(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
            'createdBy' => $this->getCreatedBy(),
            'updatedBy' => $this->getUpdatedBy(),
        ];
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
