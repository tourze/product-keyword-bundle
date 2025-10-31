<?php

namespace ProductKeywordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use ProductKeywordBundle\Entity\ProductKeyword;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * 商品-关键词关联仓储类
 *
 * @extends ServiceEntityRepository<ProductKeyword>
 */
#[AsRepository(entityClass: ProductKeyword::class)]
class ProductKeywordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProductKeyword::class);
    }

    /**
     * 根据商品ID查找关键词关联
     *
     * @return list<ProductKeyword>
     */
    public function findByProduct(string $spuId, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('pk')
            ->where('pk.spuId = :spuId')
            ->setParameter('spuId', $spuId)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
        ;

        /** @var list<ProductKeyword> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 根据关键词ID查找商品关联
     *
     * @return list<ProductKeyword>
     */
    public function findByKeywordId(string $keywordId, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('pk')
            ->where('pk.keyword = :keywordId')
            ->setParameter('keywordId', $keywordId)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
        ;

        /** @var list<ProductKeyword> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 查找商品的关键词关联，按权重排序
     *
     * @return list<ProductKeyword>
     */
    public function findByProductOrderByWeight(string $spuId, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('pk')
            ->where('pk.spuId = :spuId')
            ->setParameter('spuId', $spuId)
            ->orderBy('pk.weight', 'DESC')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
        ;

        /** @var list<ProductKeyword> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 根据来源查找关联
     *
     * @return list<ProductKeyword>
     */
    public function findBySource(string $source, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('pk')
            ->where('pk.source = :source')
            ->setParameter('source', $source)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
        ;

        /** @var list<ProductKeyword> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 查找商品的手动添加的关键词
     *
     * @return list<ProductKeyword>
     */
    public function findManualKeywords(string $spuId, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('pk')
            ->where('pk.spuId = :spuId')
            ->andWhere('pk.source = :source')
            ->setParameter('spuId', $spuId)
            ->setParameter('source', 'manual')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
        ;

        /** @var list<ProductKeyword> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 删除商品的所有关键词关联
     */
    public function deleteByProduct(string $spuId): int
    {
        $qb = $this->createQueryBuilder('pk');

        $result = $qb->delete()
            ->where('pk.spuId = :spuId')
            ->setParameter('spuId', $spuId)
            ->getQuery()
            ->execute()
        ;

        assert(is_int($result));

        return $result;
    }

    /**
     * 删除指定来源的关键词关联
     */
    public function deleteBySource(string $spuId, string $source): int
    {
        $qb = $this->createQueryBuilder('pk');

        $result = $qb->delete()
            ->where('pk.spuId = :spuId')
            ->andWhere('pk.source = :source')
            ->setParameter('spuId', $spuId)
            ->setParameter('source', $source)
            ->getQuery()
            ->execute()
        ;

        assert(is_int($result));

        return $result;
    }

    /**
     * 保存实体
     */
    public function save(ProductKeyword $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(ProductKeyword $entity, bool $flush = true): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 批量保存
     */
    /**
     * @param array<ProductKeyword> $entities
     */
    public function saveAll(array $entities, bool $flush = true): void
    {
        foreach ($entities as $entity) {
            $this->getEntityManager()->persist($entity);
        }

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 刷新实体管理器
     */
    public function flush(): void
    {
        $this->getEntityManager()->flush();
    }

    /**
     * 清空实体管理器
     */
    public function clear(): void
    {
        $this->getEntityManager()->clear();
    }
}
