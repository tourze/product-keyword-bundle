<?php

namespace ProductKeywordBundle\Interface;

use ProductKeywordBundle\DTO\KeywordDTO;
use ProductKeywordBundle\DTO\KeywordSearchCriteria;
use ProductKeywordBundle\Entity\Keyword;
use ProductKeywordBundle\Entity\ProductKeyword;
use ProductKeywordBundle\Exception\DuplicateKeywordException;
use ProductKeywordBundle\Exception\InvalidKeywordException;
use ProductKeywordBundle\Exception\KeywordNotFoundException;

/**
 * 关键词管理接口
 */
interface KeywordManagerInterface
{
    /**
     * 创建关键词
     * @throws DuplicateKeywordException 如果关键词已存在
     * @throws InvalidKeywordException 如果关键词格式不合法
     */
    public function create(KeywordDTO $dto): Keyword;

    /**
     * 更新关键词
     * @throws KeywordNotFoundException 如果关键词不存在
     */
    public function update(string $id, KeywordDTO $dto): Keyword;

    /**
     * 删除关键词（级联删除关联）
     */
    public function delete(string $id): bool;

    /**
     * 查找单个关键词
     */
    public function find(string $id): ?Keyword;

    /**
     * 按关键词名称查找
     */
    public function findByKeyword(string $keyword): ?Keyword;

    /**
     * 搜索关键词
     * @return iterable<Keyword>
     */
    public function search(KeywordSearchCriteria $criteria): iterable;

    /**
     * 关联商品和关键词
     */
    public function attachToProduct(string $spuId, string $keywordId, float $weight = 1.0, string $source = 'manual'): ProductKeyword;

    /**
     * 解除商品关键词关联
     */
    public function detachFromProduct(string $spuId, string $keywordId): bool;

    /**
     * 批量更新关键词状态
     * @param array<string> $ids
     */
    public function batchUpdateStatus(array $ids, bool $valid): int;
}
