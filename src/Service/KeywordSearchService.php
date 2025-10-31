<?php

namespace ProductKeywordBundle\Service;

use ProductKeywordBundle\Entity\Keyword;
use ProductKeywordBundle\Repository\KeywordRepository;
use ProductKeywordBundle\Repository\ProductKeywordRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * 关键词搜索服务
 */
#[Autoconfigure(public: true)]
readonly class KeywordSearchService
{
    public function __construct(
        private KeywordRepository $keywordRepository,
        private ProductKeywordRepository $productKeywordRepository,
    ) {
    }

    /**
     * 根据关键词查找相关商品，并按权重排序
     *
     * @param string $keyword 关键词
     *
     * @return array<array{productId: int, weight: float}> 返回商品ID列表，按权重降序排序
     */
    public function findProductsByKeyword(string $keyword): array
    {
        // 1. 查找关键词实体
        $keywordEntity = $this->keywordRepository->findByKeyword($keyword);
        if (null === $keywordEntity || false === $keywordEntity->isValid()) {
            return [];
        }

        // 2. 查找关键词对应的商品关联
        $qb = $this->productKeywordRepository->createQueryBuilder('pk');
        $qb->select('pk.spuId', 'pk.weight * k.weight as totalWeight')
            ->innerJoin('pk.keyword', 'k')
            ->where('k.id = :keywordId')
            ->andWhere('k.valid = true')
            ->setParameter('keywordId', $keywordEntity->getId())
            ->orderBy('totalWeight', 'DESC')
        ;

        /** @var list<array{spuId: int|string, totalWeight: float|int|string}> $results */
        $results = $qb->getQuery()->getArrayResult();

        /** @var array<array{productId: int, weight: float}> */
        return array_map(function (array $row): array {
            $spuId = $row['spuId'];
            $totalWeight = $row['totalWeight'];

            return [
                'productId' => \is_int($spuId) ? $spuId : (int) $spuId,
                'weight' => (\is_float($totalWeight) || \is_int($totalWeight)) ? (float) $totalWeight : 0.0,
            ];
        }, $results);
    }

    /**
     * 根据多个关键词查找相关商品，并按权重排序
     *
     * @param list<string> $keywords 关键词列表
     *
     * @return array<array{productId: int, weight: float}> 返回商品ID列表，按权重降序排序
     */
    public function findProductsByKeywords(array $keywords): array
    {
        if ([] === $keywords) {
            return [];
        }

        // 1. 查找所有有效的关键词实体
        $keywordEntities = $this->keywordRepository->findValidKeywordsByNames($keywords);
        if ([] === $keywordEntities) {
            return [];
        }

        // 2. 查找关键词对应的商品关联
        $qb = $this->productKeywordRepository->createQueryBuilder('pk');
        $qb->select('pk.spuId', 'SUM(pk.weight * k.weight) as totalWeight')
            ->innerJoin('pk.keyword', 'k')
            ->where($qb->expr()->in('k.id', ':keywordIds'))
            ->andWhere('k.valid = true')
            ->setParameter('keywordIds', array_map(fn (Keyword $k) => $k->getId(), $keywordEntities))
            ->groupBy('pk.spuId')
            ->orderBy('totalWeight', 'DESC')
        ;

        /** @var list<array{spuId: int|string, totalWeight: float|int|string}> $results */
        $results = $qb->getQuery()->getArrayResult();

        /** @var array<array{productId: int, weight: float}> */
        return array_map(function (array $row): array {
            $spuId = $row['spuId'];
            $totalWeight = $row['totalWeight'];

            return [
                'productId' => \is_int($spuId) ? $spuId : (int) $spuId,
                'weight' => (\is_float($totalWeight) || \is_int($totalWeight)) ? (float) $totalWeight : 0.0,
            ];
        }, $results);
    }
}
