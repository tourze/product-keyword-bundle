<?php

namespace ProductKeywordBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use ProductKeywordBundle\Entity\Keyword;
use ProductKeywordBundle\Entity\ProductKeyword;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
class ProductKeywordFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $productKeywords = [
            [
                'spuId' => 'SPU001',
                'keyword' => '智能手机',
                'weight' => 9.5,
                'source' => 'manual',
            ],
            [
                'spuId' => 'SPU001',
                'keyword' => '苹果',
                'weight' => 9.0,
                'source' => 'manual',
            ],
            [
                'spuId' => 'SPU001',
                'keyword' => '黑色',
                'weight' => 6.0,
                'source' => 'auto',
            ],
            [
                'spuId' => 'SPU002',
                'keyword' => '平板电脑',
                'weight' => 9.0,
                'source' => 'manual',
            ],
            [
                'spuId' => 'SPU002',
                'keyword' => '华为',
                'weight' => 8.5,
                'source' => 'manual',
            ],
            [
                'spuId' => 'SPU002',
                'keyword' => '无线充电',
                'weight' => 7.5,
                'source' => 'import',
            ],
            [
                'spuId' => 'SPU003',
                'keyword' => '智能手机',
                'weight' => 8.0,
                'source' => 'auto',
            ],
            [
                'spuId' => 'SPU003',
                'keyword' => '防水',
                'weight' => 7.0,
                'source' => 'manual',
            ],
            [
                'spuId' => 'SPU003',
                'keyword' => '白色',
                'weight' => 6.5,
                'source' => 'auto',
            ],
        ];

        foreach ($productKeywords as $productKeywordData) {
            $keyword = $this->getReference('keyword_' . $productKeywordData['keyword'], Keyword::class);

            $productKeyword = new ProductKeyword();
            $productKeyword->setSpuId($productKeywordData['spuId']);
            $productKeyword->setKeyword($keyword);
            $productKeyword->setWeight($productKeywordData['weight']);
            $productKeyword->setSource($productKeywordData['source']);

            $manager->persist($productKeyword);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            KeywordFixtures::class,
        ];
    }
}
