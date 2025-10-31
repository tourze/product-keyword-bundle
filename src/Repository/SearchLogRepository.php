<?php

namespace ProductKeywordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use ProductKeywordBundle\DTO\SearchLogCriteria;
use ProductKeywordBundle\Entity\SearchLog;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * 搜索日志仓储类
 * @extends ServiceEntityRepository<SearchLog>
 */
#[AsRepository(entityClass: SearchLog::class)]
class SearchLogRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SearchLog::class);
    }

    /**
     * 批量插入搜索日志
     * @param array<SearchLog> $logs
     */
    public function batchInsert(array $logs): void
    {
        $em = $this->getEntityManager();
        $batchSize = 100;

        foreach ($logs as $i => $log) {
            $em->persist($log);

            if (($i % $batchSize) === 0) {
                $em->flush();
                $em->clear();
            }
        }

        $em->flush();
        $em->clear();
    }

    /**
     * 根据条件查询搜索日志
     * @return array<SearchLog>
     */
    public function findByCriteria(SearchLogCriteria $criteria): array
    {
        $qb = $this->createQueryBuilder('s');

        if (null !== $criteria->keyword) {
            $qb->andWhere('s.keyword LIKE :keyword')
                ->setParameter('keyword', '%' . $criteria->keyword . '%')
            ;
        }

        if (null !== $criteria->userId) {
            $qb->andWhere('s.userHash = :userHash')
                ->setParameter('userHash', hash('sha256', $criteria->userId))
            ;
        }

        if (null !== $criteria->source) {
            $qb->andWhere('s.source = :source')
                ->setParameter('source', $criteria->source)
            ;
        }

        if (null !== $criteria->startDate) {
            $qb->andWhere('s.createTime >= :startDate')
                ->setParameter('startDate', $criteria->startDate)
            ;
        }

        if (null !== $criteria->endDate) {
            $qb->andWhere('s.createTime <= :endDate')
                ->setParameter('endDate', $criteria->endDate)
            ;
        }

        if (null !== $criteria->minResultCount) {
            $qb->andWhere('s.resultCount >= :minCount')
                ->setParameter('minCount', $criteria->minResultCount)
            ;
        }

        if (null !== $criteria->maxResultCount) {
            $qb->andWhere('s.resultCount <= :maxCount')
                ->setParameter('maxCount', $criteria->maxResultCount)
            ;
        }

        $qb->orderBy('s.' . $criteria->orderBy, $criteria->orderDir)
            ->setMaxResults($criteria->limit)
            ->setFirstResult(($criteria->page - 1) * $criteria->limit)
        ;

        /** @var array<SearchLog> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 删除指定日期之前的日志
     */
    public function deleteOlderThan(\DateTimeInterface $date): int
    {
        $result = $this->createQueryBuilder('s')
            ->delete()
            ->where('s.createTime < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute()
        ;

        assert(is_int($result));

        return $result;
    }

    /**
     * 删除用户的所有搜索记录
     */
    public function deleteByUserHash(string $userHash): int
    {
        $result = $this->createQueryBuilder('s')
            ->delete()
            ->where('s.userHash = :userHash')
            ->setParameter('userHash', $userHash)
            ->getQuery()
            ->execute()
        ;

        assert(is_int($result));

        return $result;
    }

    /**
     * 统计日期范围内的搜索次数
     */
    public function countByDateRange(\DateTimeInterface $start, \DateTimeInterface $end): int
    {
        $result = $this->createQueryBuilder('s')
            ->select('COUNT(s.id)')
            ->where('s.createTime BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getSingleScalarResult()
        ;

        return (int) $result;
    }

    /**
     * 获取热门关键词
     * @return array<array{keyword: string, count: int}>
     */
    public function getHotKeywords(\DateTimeInterface $start, \DateTimeInterface $end, int $limit = 100): array
    {
        /** @var array<array{keyword: string, count: int}> */
        return $this->createQueryBuilder('s')
            ->select('s.keyword as keyword, COUNT(s.id) as count')
            ->where('s.createTime BETWEEN :start AND :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('s.keyword')
            ->orderBy('count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 获取无结果的搜索词
     * @return array<array{keyword: string, count: int}>
     */
    public function getNoResultKeywords(\DateTimeInterface $start, \DateTimeInterface $end, int $limit = 100): array
    {
        /** @var array<array{keyword: string, count: int}> */
        return $this->createQueryBuilder('s')
            ->select('s.keyword as keyword, COUNT(s.id) as count')
            ->where('s.createTime BETWEEN :start AND :end')
            ->andWhere('s.resultCount = 0')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->groupBy('s.keyword')
            ->orderBy('count', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;
    }

    public function save(SearchLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(SearchLog $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }
}
