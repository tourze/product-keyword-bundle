<?php

namespace ProductKeywordBundle\Tests\Entity;

use PHPUnit\Framework\Attributes\CoversClass;
use ProductKeywordBundle\Entity\SearchLog;
use Tourze\PHPUnitDoctrineEntity\AbstractEntityTestCase;

/**
 * SearchLog 实体测试（贫血模型）
 * @internal
 */
#[CoversClass(SearchLog::class)]
final class SearchLogTest extends AbstractEntityTestCase
{
    protected function createEntity(): object
    {
        return new SearchLog();
    }

    /**
     * @return iterable<array{string, mixed}>
     */
    public static function propertiesProvider(): iterable
    {
        return [
            ['id', 123],
            ['keyword', '测试关键词'],
            ['userHash', hash('sha256', 'test')],
            ['resultCount', 100],
            ['source', 'mobile'],
            ['sessionId', 'session123'],
            ['createTime', new \DateTimeImmutable()],
        ];
    }

    public function testEntityIsInstantiable(): void
    {
        $searchLog = new SearchLog();
        $this->assertInstanceOf(SearchLog::class, $searchLog);
    }

    public function testGetterAndSetterForId(): void
    {
        $searchLog = new SearchLog();
        $searchLog->setId(999);
        $this->assertEquals(999, $searchLog->getId());
    }

    public function testGetterAndSetterForKeyword(): void
    {
        $searchLog = new SearchLog();
        $searchLog->setKeyword('手机');
        $this->assertEquals('手机', $searchLog->getKeyword());
    }

    public function testGetterAndSetterForUserHash(): void
    {
        $searchLog = new SearchLog();
        $hash = hash('sha256', 'user123salt');
        $searchLog->setUserHash($hash);
        $this->assertEquals($hash, $searchLog->getUserHash());
    }

    public function testGetterAndSetterForResultCount(): void
    {
        $searchLog = new SearchLog();
        $searchLog->setResultCount(150);
        $this->assertEquals(150, $searchLog->getResultCount());
    }

    public function testGetterAndSetterForSource(): void
    {
        $searchLog = new SearchLog();
        $searchLog->setSource('mobile');
        $this->assertEquals('mobile', $searchLog->getSource());
    }

    public function testGetterAndSetterForSessionId(): void
    {
        $searchLog = new SearchLog();
        $searchLog->setSessionId('session123');
        $this->assertEquals('session123', $searchLog->getSessionId());
    }

    public function testGetterAndSetterForCreateTime(): void
    {
        $searchLog = new SearchLog();
        $now = new \DateTimeImmutable();
        $searchLog->setCreateTime($now);
        $this->assertSame($now, $searchLog->getCreateTime());
    }

    public function testEntityHasNoBusinessLogic(): void
    {
        $searchLog = new SearchLog();
        $methods = get_class_methods($searchLog);

        foreach ($methods as $method) {
            // 贫血模型只应该有 getter/setter 和构造函数
            $this->assertMatchesRegularExpression(
                '/^(get|set|is|__construct|__toString)/',
                $method,
                "方法 {$method} 不符合贫血模型规范"
            );
        }
    }
}
