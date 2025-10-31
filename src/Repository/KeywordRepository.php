<?php

namespace ProductKeywordBundle\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use ProductKeywordBundle\Entity\Keyword;
use Tourze\CatalogBundle\Entity\Catalog;
use Tourze\PHPUnitSymfonyKernelTest\Attribute\AsRepository;

/**
 * 商品关键词仓储类
 *
 * @extends ServiceEntityRepository<Keyword>
 */
#[AsRepository(entityClass: Keyword::class)]
class KeywordRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Keyword::class);
    }

    /**
     * 根据关键词查找
     */
    public function findByKeyword(string $keyword): ?Keyword
    {
        return $this->findOneBy(['keyword' => $keyword]);
    }

    /**
     * 根据关键词列表查找有效的关键词
     *
     * @param list<string> $keywords
     *
     * @return list<Keyword>
     */
    public function findValidKeywordsByNames(array $keywords): array
    {
        /** @var list<Keyword> */
        return $this->createQueryBuilder('k')
            ->where('k.keyword IN (:keywords)')
            ->andWhere('k.valid = true')
            ->setParameter('keywords', $keywords)
            ->getQuery()
            ->getResult()
        ;
    }

    /**
     * 根据Catalog查找关键词列表
     *
     * @return list<Keyword>
     */
    public function findByCatalog(Catalog|string $catalog, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('k')
            ->where('k.catalog = :catalog')
            ->setParameter('catalog', $catalog)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
        ;

        /** @var list<Keyword> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 查找有效的关键词列表
     *
     * @return list<Keyword>
     */
    public function findValidKeywords(int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('k')
            ->where('k.valid = :isValid')
            ->setParameter('isValid', true)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
        ;

        /** @var list<Keyword> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 根据权重范围查找关键词
     *
     * @return list<Keyword>
     */
    public function findByWeightRange(float $min, float $max, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('k')
            ->where('k.weight >= :min')
            ->andWhere('k.weight <= :max')
            ->setParameter('min', $min)
            ->setParameter('max', $max)
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
        ;

        /** @var list<Keyword> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 搜索关键词
     *
     * @return list<Keyword>
     */
    public function searchKeywords(string $term, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('k')
            ->where('k.keyword LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
        ;

        /** @var list<Keyword> */
        return $qb->getQuery()->getResult();
    }

    /**
     * 保存实体
     */
    public function save(Keyword $entity, bool $flush = true): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * 删除实体
     */
    public function remove(Keyword $entity, bool $flush = true): void
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
     * @param array<Keyword> $entities
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
