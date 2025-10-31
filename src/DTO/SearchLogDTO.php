<?php

namespace ProductKeywordBundle\DTO;

/**
 * 搜索日志数据传输对象
 */
class SearchLogDTO
{
    public readonly \DateTimeImmutable $createTime;

    public function __construct(
        public readonly string $keyword,
        public readonly string $userId,
        public readonly int $resultCount,
        public readonly string $source,
        public readonly string $sessionId,
        ?\DateTimeImmutable $createTime = null,
    ) {
        $this->createTime = $createTime ?? new \DateTimeImmutable();
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        if (!\is_string($data['keyword'] ?? null)) {
            throw new \InvalidArgumentException('keyword must be a string');
        }
        if (!\is_string($data['userId'] ?? null)) {
            throw new \InvalidArgumentException('userId must be a string');
        }
        if (!\is_int($data['resultCount'] ?? null)) {
            throw new \InvalidArgumentException('resultCount must be an integer');
        }
        if (!\is_string($data['source'] ?? null)) {
            throw new \InvalidArgumentException('source must be a string');
        }
        if (!\is_string($data['sessionId'] ?? null)) {
            throw new \InvalidArgumentException('sessionId must be a string');
        }

        return new self(
            keyword: $data['keyword'],
            userId: $data['userId'],
            resultCount: $data['resultCount'],
            source: $data['source'],
            sessionId: $data['sessionId'],
            createTime: isset($data['createTime']) && \is_string($data['createTime'])
                ? new \DateTimeImmutable($data['createTime'])
                : null
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'keyword' => $this->keyword,
            'userId' => $this->userId,
            'resultCount' => $this->resultCount,
            'source' => $this->source,
            'sessionId' => $this->sessionId,
            'createTime' => $this->createTime->format('Y-m-d H:i:s'),
        ];
    }
}
