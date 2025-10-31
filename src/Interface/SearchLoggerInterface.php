<?php

namespace ProductKeywordBundle\Interface;

use ProductKeywordBundle\DTO\SearchLogCriteria;
use ProductKeywordBundle\DTO\SearchLogDTO;
use ProductKeywordBundle\Entity\SearchLog;

/**
 * 搜索记录接口
 */
interface SearchLoggerInterface
{
    /**
     * 同步记录搜索（用于重要搜索）
     */
    public function log(SearchLogDTO $dto, ?string $userSalt = null): SearchLog;

    /**
     * 异步记录搜索（推荐，高性能）
     */
    public function logAsync(SearchLogDTO $dto): void;

    /**
     * 查询搜索记录
     * @return iterable<SearchLog>
     */
    public function findLogs(SearchLogCriteria $criteria): iterable;

    /**
     * 删除用户搜索记录（GDPR合规）
     */
    public function deleteUserLogs(string $userId): int;

    /**
     * 归档历史数据
     */
    public function archiveLogs(\DateTimeInterface $before): int;
}
