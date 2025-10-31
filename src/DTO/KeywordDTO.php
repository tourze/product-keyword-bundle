<?php

namespace ProductKeywordBundle\DTO;

/**
 * 关键词数据传输对象
 */
class KeywordDTO
{
    public function __construct(
        public readonly string $keyword,
        public readonly float $weight = 1.0,
        public readonly ?string $parentId = null,
        public readonly bool $valid = true,
        public readonly bool $recommend = false,
        public readonly ?string $description = null,
    ) {
    }

    /**
     * @param non-empty-array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (!\is_string($data['keyword']) || '' === $data['keyword']) {
            throw new \InvalidArgumentException('keyword must be a non-empty string');
        }

        $weight = $data['weight'] ?? 1.0;
        if (!\is_float($weight) && !\is_int($weight)) {
            $weight = 1.0;
        }

        return new self(
            keyword: $data['keyword'],
            weight: (float) $weight,
            parentId: isset($data['parentId']) && \is_string($data['parentId']) ? $data['parentId'] : null,
            valid: (bool) ($data['valid'] ?? true),
            recommend: (bool) ($data['recommend'] ?? false),
            description: isset($data['description']) && \is_string($data['description']) ? $data['description'] : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'keyword' => $this->keyword,
            'weight' => $this->weight,
            'parentId' => $this->parentId,
            'valid' => $this->valid,
            'recommend' => $this->recommend,
            'description' => $this->description,
        ];
    }
}
