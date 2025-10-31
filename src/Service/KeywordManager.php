<?php

namespace ProductKeywordBundle\Service;

use Doctrine\ORM\EntityManagerInterface;
use ProductKeywordBundle\DTO\KeywordDTO;
use ProductKeywordBundle\DTO\KeywordSearchCriteria;
use ProductKeywordBundle\Entity\Keyword;
use ProductKeywordBundle\Entity\ProductKeyword;
use ProductKeywordBundle\Event\KeywordCreatedEvent;
use ProductKeywordBundle\Event\KeywordDeletedEvent;
use ProductKeywordBundle\Event\KeywordUpdatedEvent;
use ProductKeywordBundle\Exception\DuplicateKeywordException;
use ProductKeywordBundle\Exception\InvalidKeywordException;
use ProductKeywordBundle\Exception\KeywordNotFoundException;
use ProductKeywordBundle\Interface\KeywordManagerInterface;
use ProductKeywordBundle\Repository\KeywordRepository;
use ProductKeywordBundle\Repository\ProductKeywordRepository;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * 关键词管理服务
 */
#[Autoconfigure(public: true)]
readonly class KeywordManager implements KeywordManagerInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private KeywordRepository $keywordRepository,
        private ProductKeywordRepository $productKeywordRepository,
        private EventDispatcherInterface $eventDispatcher,
        private KeywordValidator $validator,
    ) {
    }

    public function create(KeywordDTO $dto): Keyword
    {
        // 验证关键词
        $this->validator->validate($dto->keyword);

        // 检查重复
        $existing = $this->keywordRepository->findByKeyword($dto->keyword);
        if (null !== $existing) {
            throw DuplicateKeywordException::forKeyword($dto->keyword);
        }

        // 创建实体
        $keyword = new Keyword();
        $keyword->setKeyword($dto->keyword);
        $keyword->setWeight($dto->weight);
        $keyword->setValid($dto->valid);
        $keyword->setRecommend($dto->recommend);
        $keyword->setDescription($dto->description);

        // 设置父级
        if (null !== $dto->parentId) {
            $parent = $this->keywordRepository->find($dto->parentId);
            if (null === $parent) {
                throw new KeywordNotFoundException($dto->parentId);
            }
            $keyword->setParent($parent);
        }

        // 持久化
        $this->entityManager->persist($keyword);
        $this->entityManager->flush();

        // 分发事件
        $this->eventDispatcher->dispatch(new KeywordCreatedEvent($keyword));

        return $keyword;
    }

    public function update(string $id, KeywordDTO $dto): Keyword
    {
        $keyword = $this->keywordRepository->find($id);
        if (null === $keyword) {
            throw KeywordNotFoundException::forId($id);
        }

        // 验证新关键词
        if ($dto->keyword !== $keyword->getKeyword()) {
            $this->validator->validate($dto->keyword);

            // 检查重复
            $existing = $this->keywordRepository->findByKeyword($dto->keyword);
            if (null !== $existing && $existing->getId() !== $id) {
                throw DuplicateKeywordException::forKeyword($dto->keyword);
            }

            $keyword->setKeyword($dto->keyword);
        }

        $keyword->setWeight($dto->weight);
        $keyword->setValid($dto->valid);
        $keyword->setRecommend($dto->recommend);
        $keyword->setDescription($dto->description);

        // 更新父级
        if (null !== $dto->parentId) {
            $parent = $this->keywordRepository->find($dto->parentId);
            if (null === $parent) {
                throw new KeywordNotFoundException($dto->parentId);
            }
            $keyword->setParent($parent);
        } else {
            $keyword->setParent(null);
        }

        $this->entityManager->flush();

        // 分发事件
        $this->eventDispatcher->dispatch(new KeywordUpdatedEvent($keyword));

        return $keyword;
    }

    public function delete(string $id): bool
    {
        $keyword = $this->keywordRepository->find($id);
        if (null === $keyword) {
            throw new KeywordNotFoundException('Keyword not found with id: ' . $id);
        }

        // 级联删除由 Doctrine 处理
        $this->entityManager->remove($keyword);
        $this->entityManager->flush();

        // 分发事件
        $this->eventDispatcher->dispatch(new KeywordDeletedEvent($keyword));

        return true;
    }

    public function find(string $id): ?Keyword
    {
        return $this->keywordRepository->find($id);
    }

    public function findByKeyword(string $keyword): ?Keyword
    {
        return $this->keywordRepository->findByKeyword($keyword);
    }

    /**
     * @return iterable<Keyword>
     */
    public function search(KeywordSearchCriteria $criteria): iterable
    {
        $qb = $this->keywordRepository->createQueryBuilder('k');

        if (null !== $criteria->keyword) {
            $qb->andWhere('k.keyword LIKE :keyword')
                ->setParameter('keyword', '%' . $criteria->keyword . '%')
            ;
        }

        if (null !== $criteria->parentId) {
            $qb->andWhere('k.parent = :parentId')
                ->setParameter('parentId', $criteria->parentId)
            ;
        }

        if (null !== $criteria->valid) {
            $qb->andWhere('k.valid = :valid')
                ->setParameter('valid', $criteria->valid)
            ;
        }

        if (null !== $criteria->recommend) {
            $qb->andWhere('k.recommend = :recommend')
                ->setParameter('recommend', $criteria->recommend)
            ;
        }

        if (null !== $criteria->minWeight) {
            $qb->andWhere('k.weight >= :minWeight')
                ->setParameter('minWeight', $criteria->minWeight)
            ;
        }

        if (null !== $criteria->maxWeight) {
            $qb->andWhere('k.weight <= :maxWeight')
                ->setParameter('maxWeight', $criteria->maxWeight)
            ;
        }

        $qb->orderBy('k.' . $criteria->orderBy, $criteria->orderDir)
            ->setMaxResults($criteria->limit)
            ->setFirstResult(($criteria->page - 1) * $criteria->limit)
        ;

        /** @var iterable<Keyword> */
        return $qb->getQuery()->getResult();
    }

    public function attachToProduct(string $spuId, string $keywordId, float $weight = 1.0, string $source = 'manual'): ProductKeyword
    {
        $keyword = $this->keywordRepository->find($keywordId);
        if (null === $keyword) {
            throw KeywordNotFoundException::forId($keywordId);
        }

        // 检查是否已存在
        $existing = $this->productKeywordRepository->findOneBy([
            'spuId' => $spuId,
            'keyword' => $keyword,
        ]);

        if (null !== $existing) {
            // 更新权重
            $existing->setWeight($weight);
            $existing->setSource($source);
            $this->entityManager->flush();

            return $existing;
        }

        // 创建新关联
        $productKeyword = new ProductKeyword();
        $productKeyword->setSpuId($spuId);
        $productKeyword->setKeyword($keyword);
        $productKeyword->setWeight($weight);
        $productKeyword->setSource($source);

        $this->entityManager->persist($productKeyword);
        $this->entityManager->flush();

        return $productKeyword;
    }

    public function detachFromProduct(string $spuId, string $keywordId): bool
    {
        $keyword = $this->keywordRepository->find($keywordId);
        if (null === $keyword) {
            return false;
        }

        $productKeyword = $this->productKeywordRepository->findOneBy([
            'spuId' => $spuId,
            'keyword' => $keyword,
        ]);

        if (null === $productKeyword) {
            return false;
        }

        $this->entityManager->remove($productKeyword);
        $this->entityManager->flush();

        return true;
    }

    public function batchUpdateStatus(array $ids, bool $valid): int
    {
        if ([] === $ids) {
            return 0;
        }

        $result = $this->keywordRepository->createQueryBuilder('k')
            ->update()
            ->set('k.valid', ':valid')
            ->where('k.id IN (:ids)')
            ->setParameter('valid', $valid)
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute()
        ;
        \assert(\is_int($result));

        return $result;
    }
}
