<?php

namespace ProductKeywordBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use ProductKeywordBundle\DTO\SearchLogCriteria;
use ProductKeywordBundle\DTO\SearchLogDTO;
use ProductKeywordBundle\Entity\SearchLog;
use ProductKeywordBundle\Interface\SearchLoggerInterface;
use ProductKeywordBundle\Repository\SearchLogRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * 搜索记录服务
 */
#[Autoconfigure(public: true)]
class SearchLogger implements SearchLoggerInterface
{
    private bool $asyncEnabled;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SearchLogRepository $repository,
        private readonly ?MessageBusInterface $messageBus = null,
    ) {
        $this->asyncEnabled = (bool) ($_ENV['KEYWORD_ASYNC_LOG'] ?? true);
    }

    public function log(SearchLogDTO $dto, ?string $userSalt = null): SearchLog
    {
        $searchLog = new SearchLog();
        $searchLog->setKeyword($dto->keyword);
        $searchLog->setUserHash($this->anonymizeUser($dto->userId, $userSalt));
        $searchLog->setResultCount($dto->resultCount);
        $searchLog->setSource($dto->source);
        $searchLog->setSessionId($dto->sessionId);
        $searchLog->setCreateTime($dto->createTime);

        $this->entityManager->persist($searchLog);
        $this->entityManager->flush();

        return $searchLog;
    }

    public function logAsync(SearchLogDTO $dto): void
    {
        if (!$this->asyncEnabled || null === $this->messageBus) {
            // 降级到同步
            $this->log($dto);

            return;
        }

        // TODO: 实现异步消息处理
        // $this->messageBus->dispatch(new LogSearchMessage($dto));

        // 暂时使用同步
        $this->log($dto);
    }

    public function findLogs(SearchLogCriteria $criteria): iterable
    {
        return $this->repository->findByCriteria($criteria);
    }

    public function deleteUserLogs(string $userId): int
    {
        $userHash = $this->anonymizeUser($userId);

        return $this->repository->deleteByUserHash($userHash);
    }

    public function archiveLogs(\DateTimeInterface $before): int
    {
        // TODO: 实现归档逻辑
        return $this->repository->deleteOlderThan($before);
    }

    private function anonymizeUser(string $userId, ?string $userSalt = null): string
    {
        $envSalt = $_ENV['USER_SALT'] ?? 'default';
        \assert(\is_string($envSalt));
        $salt = $userSalt ?? $envSalt;

        return hash('sha256', $userId . $salt);
    }
}
