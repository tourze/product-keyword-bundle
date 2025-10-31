<?php

namespace ProductKeywordBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use ProductKeywordBundle\Entity\Keyword;
use Symfony\Component\DependencyInjection\Attribute\When;

#[When(env: 'test')]
#[When(env: 'dev')]
class KeywordFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $keywords = [
            [
                'keyword' => '智能手机',
                'weight' => 9.0,
                'description' => '智能手机产品关键词',
                'recommend' => true,
            ],
            [
                'keyword' => '平板电脑',
                'weight' => 8.5,
                'description' => '平板电脑产品关键词',
                'recommend' => true,
            ],
            [
                'keyword' => '苹果',
                'weight' => 9.5,
                'description' => '苹果品牌关键词',
                'recommend' => true,
            ],
            [
                'keyword' => '华为',
                'weight' => 9.0,
                'description' => '华为品牌关键词',
                'recommend' => true,
            ],
            [
                'keyword' => '防水',
                'weight' => 7.0,
                'description' => '防水功能关键词',
                'recommend' => false,
            ],
            [
                'keyword' => '无线充电',
                'weight' => 8.0,
                'description' => '无线充电功能关键词',
                'recommend' => true,
            ],
            [
                'keyword' => '金属',
                'weight' => 6.5,
                'description' => '金属材质关键词',
                'recommend' => false,
            ],
            [
                'keyword' => '塑料',
                'weight' => 5.0,
                'description' => '塑料材质关键词',
                'recommend' => false,
            ],
            [
                'keyword' => '黑色',
                'weight' => 7.5,
                'description' => '黑色关键词',
                'recommend' => true,
            ],
            [
                'keyword' => '白色',
                'weight' => 7.0,
                'description' => '白色关键词',
                'recommend' => true,
            ],
        ];

        foreach ($keywords as $keywordData) {
            $keyword = new Keyword();
            $keyword->setKeyword($keywordData['keyword']);
            $keyword->setWeight($keywordData['weight']);
            $keyword->setDescription($keywordData['description']);
            $keyword->setRecommend($keywordData['recommend']);
            $keyword->setValid(true);

            $this->addReference('keyword_' . $keywordData['keyword'], $keyword);
            $manager->persist($keyword);
        }

        $manager->flush();
    }
}
