<?php

namespace ProductKeywordBundle\Service;

use ProductKeywordBundle\DTO\DateRange;
use ProductKeywordBundle\Interface\SearchOptimizerInterface;
use ProductKeywordBundle\Repository\KeywordRepository;
use ProductKeywordBundle\Repository\SearchLogRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;

/**
 * 搜索优化服务
 */
#[Autoconfigure(public: true)]
readonly class SearchOptimizer implements SearchOptimizerInterface
{
    public function __construct(
        private KeywordRepository $keywordRepository,
        private SearchLogRepository $searchLogRepository,
    ) {
    }

    /**
     * @return array<string>
     */
    public function recommend(string $query, int $limit = 10): array
    {
        // TODO: 实现关键词推荐算法
        $keywords = $this->keywordRepository->createQueryBuilder('k')
            ->where('k.keyword LIKE :query')
            ->andWhere('k.valid = true')
            ->andWhere('k.recommend = true')
            ->setParameter('query', '%' . $query . '%')
            ->orderBy('k.weight', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult()
        ;

        if (!\is_array($keywords)) {
            return [];
        }

        /** @var array<string> */
        return array_values(array_map(function (mixed $k): string {
            if (\is_object($k) && method_exists($k, 'getKeyword')) {
                $keyword = $k->getKeyword();

                return \is_string($keyword) ? $keyword : '';
            }

            return '';
        }, $keywords));
    }

    public function correct(string $query): ?string
    {
        // TODO: 实现拼写纠错
        return null;
    }

    public function getSynonyms(string $keyword): array
    {
        // TODO: 实现同义词获取
        return [];
    }

    public function extractKeywords(DateRange $range, int $limit = 100): array
    {
        $hotKeywords = $this->searchLogRepository->getHotKeywords(
            $range->getStart(),
            $range->getEnd(),
            $limit
        );

        return array_map(function ($item) {
            return [
                'keyword' => $item['keyword'],
                'frequency' => $item['count'],
            ];
        }, $hotKeywords);
    }

    public function optimizeWeights(string $strategy = 'conversion'): void
    {
        // TODO: 实现权重优化策略
    }
}
