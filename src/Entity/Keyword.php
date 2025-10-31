<?php

declare(strict_types=1);

namespace ProductKeywordBundle\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ProductKeywordBundle\Repository\KeywordRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\Arrayable\AdminArrayInterface;
use Tourze\Arrayable\ApiArrayInterface;
use Tourze\Arrayable\PlainArrayInterface;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;
use Tourze\DoctrineSnowflakeBundle\Traits\SnowflakeKeyAware;
use Tourze\DoctrineTimestampBundle\Traits\TimestampableAware;
use Tourze\DoctrineTrackBundle\Attribute\TrackColumn;
use Tourze\DoctrineUserBundle\Traits\BlameableAware;

/**
 * 商品关键词
 *
 * @implements PlainArrayInterface<string, mixed>
 * @implements ApiArrayInterface<string, mixed>
 * @implements AdminArrayInterface<string, mixed>
 */
#[ORM\Entity(repositoryClass: KeywordRepository::class)]
#[ORM\Table(name: 'product_keyword', options: ['comment' => '商品关键词'])]
class Keyword implements PlainArrayInterface, ApiArrayInterface, AdminArrayInterface, \Stringable
{
    use TimestampableAware;
    use BlameableAware;
    use SnowflakeKeyAware;

    #[IndexColumn]
    #[TrackColumn]
    #[Assert\Type(type: 'bool')]
    #[ORM\Column(type: Types::BOOLEAN, nullable: true, options: ['comment' => '有效', 'default' => 0])]
    private ?bool $valid = false;

    #[IndexColumn]
    #[ORM\Column(type: Types::STRING, length: 100, unique: true, options: ['comment' => '关键词'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    private string $keyword;

    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, options: ['comment' => '父级关键词ID'])]
    private ?self $parent = null;

    #[IndexColumn]
    #[Assert\Range(min: 0, max: 100)]
    #[ORM\Column(type: Types::FLOAT, options: ['default' => 1.0, 'comment' => '权重值'])]
    private float $weight = 1.0;

    #[Assert\Length(max: 255)]
    #[Assert\Url]
    #[ORM\Column(length: 255, nullable: true, options: ['comment' => '缩略图'])]
    private ?string $thumb = null;

    #[Assert\Length(max: 65535)]
    #[ORM\Column(type: Types::TEXT, nullable: true, options: ['comment' => '描述'])]
    private ?string $description = null;

    /**
     * @var Collection<int, ProductKeyword>
     */
    #[ORM\OneToMany(mappedBy: 'keyword', targetEntity: ProductKeyword::class)]
    private Collection $productKeywords;

    #[Assert\Type(type: 'bool')]
    #[ORM\Column(nullable: true, options: ['comment' => '是否推荐'])]
    private ?bool $recommend = false;

    public function __construct()
    {
        $this->productKeywords = new ArrayCollection();
    }

    public function setValid(?bool $valid): void
    {
        $this->valid = $valid;
    }

    public function getThumb(): ?string
    {
        return $this->thumb;
    }

    public function setThumb(?string $thumb): void
    {
        $this->thumb = $thumb;
    }

    /**
     * @return Collection<int, ProductKeyword>
     */
    public function getProductKeywords(): Collection
    {
        return $this->productKeywords;
    }

    public function getProductCount(): int
    {
        return $this->productKeywords->count();
    }

    public function setRecommend(?bool $recommend): void
    {
        $this->recommend = $recommend;
    }

    /**
     * @return array<string, mixed>
     */
    public function retrieveApiArray(): array
    {
        return [
            'id' => $this->getId(),
            'keyword' => $this->getKeyword(),
            'weight' => $this->getWeight(),
        ];
    }

    public function getKeyword(): ?string
    {
        return $this->keyword;
    }

    public function setKeyword(string $keyword): void
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
        $parentData = null;
        if (null !== $this->parent) {
            $parentData = [
                'id' => $this->parent->getId(),
                'keyword' => $this->parent->getKeyword(),
            ];
        }

        return [
            'id' => $this->getId(),
            'keyword' => $this->getKeyword(),
            'parent' => $parentData,
            'parentName' => $this->getParentName(),
            'weight' => $this->getWeight(),
            'description' => $this->getDescription(),
            'valid' => $this->isValid(),
            'recommend' => $this->isRecommend(),
            'createTime' => $this->getCreateTime()?->format('Y-m-d H:i:s'),
            'updateTime' => $this->getUpdateTime()?->format('Y-m-d H:i:s'),
            'createdBy' => $this->getCreatedBy(),
            'updatedBy' => $this->getUpdatedBy(),
        ];
    }

    public function getParent(): ?self
    {
        return $this->parent;
    }

    public function setParent(?self $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * 获取父级关键词名称
     */
    public function getParentName(): ?string
    {
        return $this->parent?->getKeyword();
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function isValid(): ?bool
    {
        return $this->valid;
    }

    public function isRecommend(): ?bool
    {
        return $this->recommend;
    }

    public function __toString(): string
    {
        return (string) $this->getId();
    }
}
