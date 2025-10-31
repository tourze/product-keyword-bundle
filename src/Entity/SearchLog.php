<?php

namespace ProductKeywordBundle\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use ProductKeywordBundle\Repository\SearchLogRepository;
use Symfony\Component\Validator\Constraints as Assert;
use Tourze\DoctrineIndexedBundle\Attribute\IndexColumn;

/**
 * 搜索日志实体（贫血模型）
 */
#[ORM\Entity(repositoryClass: SearchLogRepository::class)]
#[ORM\Table(name: 'search_log', options: ['comment' => '搜索日志表'])]
class SearchLog implements \Stringable
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::BIGINT, options: ['comment' => 'ID'])]
    private ?int $id = null;

    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 200)]
    #[ORM\Column(length: 200, options: ['comment' => '搜索关键词'])]
    private string $keyword;

    #[IndexColumn]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    #[ORM\Column(length: 64, options: ['comment' => '用户哈希'])]
    private string $userHash;

    #[Assert\PositiveOrZero]
    #[ORM\Column(type: Types::INTEGER, options: ['comment' => '结果数量'])]
    private int $resultCount = 0;

    #[Assert\NotBlank]
    #[Assert\Length(max: 20)]
    #[ORM\Column(length: 20, options: ['comment' => '搜索来源'])]
    private string $source;

    #[Assert\NotBlank]
    #[Assert\Length(max: 100)]
    #[ORM\Column(length: 100, options: ['comment' => '会话ID'])]
    private string $sessionId;

    #[IndexColumn]
    #[Assert\NotNull]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, options: ['comment' => '创建时间'])]
    private \DateTimeImmutable $createTime;

    public function __construct()
    {
        $this->createTime = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(int $id): void
    {
        $this->id = $id;
    }

    public function getKeyword(): string
    {
        return $this->keyword;
    }

    public function setKeyword(string $keyword): void
    {
        $this->keyword = $keyword;
    }

    public function getUserHash(): string
    {
        return $this->userHash;
    }

    public function setUserHash(string $userHash): void
    {
        $this->userHash = $userHash;
    }

    public function getResultCount(): int
    {
        return $this->resultCount;
    }

    public function setResultCount(int $resultCount): void
    {
        $this->resultCount = $resultCount;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function setSource(string $source): void
    {
        $this->source = $source;
    }

    public function getSessionId(): string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): void
    {
        $this->sessionId = $sessionId;
    }

    public function getCreateTime(): \DateTimeImmutable
    {
        return $this->createTime;
    }

    public function setCreateTime(\DateTimeImmutable $createTime): void
    {
        $this->createTime = $createTime;
    }

    public function __toString(): string
    {
        return sprintf('[%s] %s', $this->id ?? 'new', $this->keyword ?? '');
    }
}
