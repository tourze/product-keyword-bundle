<?php

namespace ProductKeywordBundle\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use ProductKeywordBundle\Entity\SearchLog;

class SearchLogFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $searches = [
            ['keyword' => 'iPhone', 'results' => 150, 'source' => 'web'],
            ['keyword' => '笔记本电脑', 'results' => 200, 'source' => 'app'],
            ['keyword' => '耳机', 'results' => 80, 'source' => 'web'],
            ['keyword' => '键盘', 'results' => 45, 'source' => 'api'],
            ['keyword' => '显示器', 'results' => 30, 'source' => 'web'],
        ];

        foreach ($searches as $index => $searchData) {
            $searchLog = new SearchLog();
            $searchLog->setKeyword($searchData['keyword']);
            $searchLog->setUserHash(md5('user' . ($index + 1)));
            $searchLog->setResultCount($searchData['results']);
            $searchLog->setSource($searchData['source']);
            $searchLog->setSessionId('session_' . uniqid());

            $manager->persist($searchLog);
        }

        $manager->flush();
    }
}
