# Product Keyword Bundle 技术设计

## 1. 技术概览

### 1.1 架构模式
- **扁平化 Service 层**：所有业务逻辑集中在 Service 层，不进行分层
- **贫血模型实体**：实体只包含数据和 getter/setter，不包含业务逻辑
- **事件驱动**：使用 Symfony EventDispatcher 进行组件通信
- **异步处理**：使用消息队列处理高并发搜索记录写入

### 1.2 核心设计原则
- **KISS**：保持简单直接，避免过度抽象
- **YAGNI**：只实现当前需要的功能
- **单一职责**：每个 Service 负责一个明确的功能域
- **性能优先**：支持高并发和大数据量

### 1.3 技术决策理由
- 选择扁平化架构：降低复杂度，提高可维护性
- 使用贫血模型：业务逻辑集中管理，便于测试和修改
- 异步写入设计：避免搜索记录影响主流程性能
- 缓存策略：提高关键词查询和推荐性能

## 2. 公共API设计

### 2.1 关键词管理接口

```php
namespace ProductKeywordBundle\Interface;

interface KeywordManagerInterface
{
    /**
     * 创建关键词
     * @throws DuplicateKeywordException 如果关键词已存在
     * @throws InvalidKeywordException 如果关键词格式不合法
     */
    public function create(KeywordDTO $dto): Keyword;
    
    /**
     * 更新关键词
     * @throws KeywordNotFoundException 如果关键词不存在
     */
    public function update(int $id, KeywordDTO $dto): Keyword;
    
    /**
     * 删除关键词（级联删除关联）
     */
    public function delete(int $id): bool;
    
    /**
     * 查找单个关键词
     */
    public function find(int $id): ?Keyword;
    
    /**
     * 按关键词名称查找
     */
    public function findByKeyword(string $keyword): ?Keyword;
    
    /**
     * 搜索关键词
     */
    public function search(KeywordSearchCriteria $criteria): KeywordCollection;
    
    /**
     * 关联商品和关键词
     */
    public function attachToProduct(string $spuId, int $keywordId, float $weight = 1.0, string $source = 'manual'): ProductKeyword;
    
    /**
     * 解除商品关键词关联
     */
    public function detachFromProduct(string $spuId, int $keywordId): bool;
    
    /**
     * 批量更新关键词状态
     */
    public function batchUpdateStatus(array $ids, bool $valid): int;
}
```

### 2.2 搜索记录接口

```php
namespace ProductKeywordBundle\Interface;

interface SearchLoggerInterface
{
    /**
     * 同步记录搜索（用于重要搜索）
     */
    public function log(SearchLogDTO $dto): void;
    
    /**
     * 异步记录搜索（推荐，高性能）
     */
    public function logAsync(SearchLogDTO $dto): void;
    
    /**
     * 查询搜索记录
     */
    public function findLogs(SearchLogCriteria $criteria): SearchLogCollection;
    
    /**
     * 删除用户搜索记录（GDPR合规）
     */
    public function deleteUserLogs(string $userId): int;
    
    /**
     * 归档历史数据
     */
    public function archiveLogs(\DateTimeInterface $before): int;
}
```

### 2.3 搜索分析接口

```php
namespace ProductKeywordBundle\Interface;

interface SearchAnalyzerInterface
{
    /**
     * 分析热门关键词
     * @return array{keyword: string, count: int, trend: float}[]
     */
    public function analyzeHotKeywords(DateRange $range, int $limit = 100): array;
    
    /**
     * 分析搜索命中率
     * @return array{total_searches: int, no_result_searches: int, hit_rate: float}
     */
    public function analyzeHitRate(DateRange $range): array;
    
    /**
     * 分析搜索转化率
     * @return array{keyword: string, searches: int, clicks: int, conversions: int, rate: float}[]
     */
    public function analyzeConversion(DateRange $range): array;
    
    /**
     * 分析关键词趋势
     * @return array{date: string, count: int}[]
     */
    public function analyzeTrends(string $keyword, DateRange $range): array;
    
    /**
     * 识别无结果搜索词
     * @return array{keyword: string, count: int}[]
     */
    public function findNoResultKeywords(DateRange $range, int $limit = 100): array;
}
```

### 2.4 搜索优化接口

```php
namespace ProductKeywordBundle\Interface;

interface SearchOptimizerInterface
{
    /**
     * 推荐相关关键词
     * @return string[]
     */
    public function recommend(string $query, int $limit = 10): array;
    
    /**
     * 纠正拼写错误
     */
    public function correct(string $query): ?string;
    
    /**
     * 获取同义词
     * @return string[]
     */
    public function getSynonyms(string $keyword): array;
    
    /**
     * 自动提取关键词
     * @return array{keyword: string, frequency: int}[]
     */
    public function extractKeywords(DateRange $range, int $limit = 100): array;
    
    /**
     * 优化关键词权重
     */
    public function optimizeWeights(OptimizationStrategy $strategy): void;
}
```

### 2.5 使用示例

```php
// 创建关键词
$keywordManager = $container->get(KeywordManagerInterface::class);
$keyword = $keywordManager->create(new KeywordDTO(
    keyword: '智能手机',
    weight: 10.0,
    parent: null,
    valid: true,
    recommend: true
));

// 关联商品
$keywordManager->attachToProduct('SPU123', $keyword->getId(), 5.0, 'manual');

// 记录搜索
$searchLogger = $container->get(SearchLoggerInterface::class);
$searchLogger->logAsync(new SearchLogDTO(
    keyword: '智能手机',
    userId: 'user123',
    resultCount: 156,
    source: 'mobile',
    sessionId: 'session456'
));

// 分析热门关键词
$analyzer = $container->get(SearchAnalyzerInterface::class);
$hotKeywords = $analyzer->analyzeHotKeywords(
    new DateRange(new DateTime('-7 days'), new DateTime()),
    50
);

// 推荐关键词
$optimizer = $container->get(SearchOptimizerInterface::class);
$recommendations = $optimizer->recommend('手机', 10);
```

## 3. 内部架构

### 3.1 核心组件划分

```
packages/product-keyword-bundle/
├── src/
│   ├── Entity/              # 贫血模型实体
│   │   ├── Keyword.php
│   │   ├── ProductKeyword.php
│   │   ├── SearchLog.php
│   │   └── SearchSession.php
│   │
│   ├── Repository/          # 数据访问层
│   │   ├── KeywordRepository.php
│   │   ├── ProductKeywordRepository.php
│   │   ├── SearchLogRepository.php
│   │   └── SearchSessionRepository.php
│   │
│   ├── Service/            # 扁平化业务逻辑层
│   │   ├── KeywordManager.php
│   │   ├── SearchLogger.php
│   │   ├── SearchAnalyzer.php
│   │   ├── SearchOptimizer.php
│   │   ├── KeywordValidator.php
│   │   ├── SpellChecker.php
│   │   ├── SynonymService.php
│   │   └── WeightCalculator.php
│   │
│   ├── DTO/                # 数据传输对象
│   │   ├── KeywordDTO.php
│   │   ├── SearchLogDTO.php
│   │   ├── KeywordSearchCriteria.php
│   │   └── SearchLogCriteria.php
│   │
│   ├── Event/              # 事件类
│   │   ├── KeywordCreatedEvent.php
│   │   ├── KeywordUpdatedEvent.php
│   │   ├── KeywordDeletedEvent.php
│   │   ├── SearchExecutedEvent.php
│   │   └── SearchNoResultEvent.php
│   │
│   ├── Exception/          # 异常类
│   │   ├── KeywordException.php
│   │   ├── DuplicateKeywordException.php
│   │   ├── InvalidKeywordException.php
│   │   └── KeywordNotFoundException.php
│   │
│   ├── Message/            # 异步消息
│   │   ├── LogSearchMessage.php
│   │   └── ArchiveLogsMessage.php
│   │
│   ├── MessageHandler/     # 消息处理器
│   │   ├── LogSearchMessageHandler.php
│   │   └── ArchiveLogsMessageHandler.php
│   │
│   ├── Command/            # CLI命令
│   │   ├── ImportKeywordsCommand.php
│   │   ├── AnalyzeSearchCommand.php
│   │   ├── OptimizeWeightsCommand.php
│   │   └── ArchiveLogsCommand.php
│   │
│   └── Interface/          # 公共接口
│       ├── KeywordManagerInterface.php
│       ├── SearchLoggerInterface.php
│       ├── SearchAnalyzerInterface.php
│       └── SearchOptimizerInterface.php
```

### 3.2 数据流设计

#### 3.2.1 关键词管理流程
```
用户请求 → KeywordManager → KeywordValidator → KeywordRepository → 数据库
                ↓
         EventDispatcher → KeywordCreatedEvent → 监听器
```

#### 3.2.2 搜索记录流程
```
搜索请求 → SearchLogger → MessageBus → LogSearchMessage → Queue
                                              ↓
                                    LogSearchMessageHandler → SearchLogRepository → 数据库
```

#### 3.2.3 搜索分析流程
```
分析请求 → SearchAnalyzer → SearchLogRepository → 数据聚合
                ↓
            CacheService → Redis缓存
```

### 3.3 实体设计

#### 3.3.1 Keyword 实体
```php
namespace ProductKeywordBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: KeywordRepository::class)]
#[ORM\Table(name: 'product_keyword')]
#[ORM\UniqueConstraint(columns: ['keyword'])]
class Keyword
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;
    
    #[ORM\Column(length: 100, unique: true)]
    private string $keyword;
    
    #[ORM\ManyToOne(targetEntity: self::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?self $parent = null;
    
    #[ORM\Column(type: 'float')]
    private float $weight = 1.0;
    
    #[ORM\Column(type: 'boolean')]
    private bool $valid = true;
    
    #[ORM\Column(type: 'boolean')]
    private bool $recommend = false;
    
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;
    
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createTime;
    
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $updateTime;
    
    // Getter/Setter methods only
}
```

#### 3.3.2 SearchLog 实体
```php
namespace ProductKeywordBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SearchLogRepository::class)]
#[ORM\Table(name: 'search_log')]
#[ORM\Index(columns: ['keyword'])]
#[ORM\Index(columns: ['user_hash'])]
#[ORM\Index(columns: ['create_time'])]
class SearchLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'bigint')]
    private ?int $id = null;
    
    #[ORM\Column(length: 200)]
    private string $keyword;
    
    #[ORM\Column(length: 64)]
    private string $userHash; // 匿名化用户标识
    
    #[ORM\Column(type: 'integer')]
    private int $resultCount = 0;
    
    #[ORM\Column(length: 20)]
    private string $source; // pc/mobile/app
    
    #[ORM\Column(length: 100)]
    private string $sessionId;
    
    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createTime;
    
    // Getter/Setter methods only
}
```

## 4. 扩展机制

### 4.1 分析器扩展点

```php
namespace ProductKeywordBundle\Interface;

interface AnalyzerExtensionInterface
{
    /**
     * 自定义分析逻辑
     */
    public function analyze(array $searchLogs): array;
    
    /**
     * 获取分析器名称
     */
    public function getName(): string;
    
    /**
     * 获取优先级（数字越大优先级越高）
     */
    public function getPriority(): int;
}
```

### 4.2 优化器扩展点

```php
namespace ProductKeywordBundle\Interface;

interface OptimizerExtensionInterface
{
    /**
     * 自定义优化策略
     */
    public function optimize(array $keywords): void;
    
    /**
     * 是否支持该优化场景
     */
    public function supports(string $scenario): bool;
}
```

### 4.3 事件系统

#### 4.3.1 事件定义
```php
// 关键词创建事件
class KeywordCreatedEvent
{
    public function __construct(
        private readonly Keyword $keyword
    ) {}
}

// 搜索执行事件
class SearchExecutedEvent
{
    public function __construct(
        private readonly string $keyword,
        private readonly int $resultCount
    ) {}
}
```

#### 4.3.2 事件监听器示例
```php
class SearchStatisticsListener
{
    public function onSearchExecuted(SearchExecutedEvent $event): void
    {
        // 更新统计数据
        $this->statisticsService->increment(
            $event->getKeyword(),
            $event->getResultCount()
        );
    }
}
```

### 4.4 配置架构

```php
// 通过环境变量配置
class SearchLogger
{
    private bool $asyncEnabled;
    private int $batchSize;
    
    public function __construct()
    {
        $this->asyncEnabled = (bool) ($_ENV['KEYWORD_ASYNC_LOG'] ?? true);
        $this->batchSize = (int) ($_ENV['KEYWORD_LOG_BATCH_SIZE'] ?? 100);
    }
}
```

## 5. 集成设计

### 5.1 Symfony Bundle 集成

```php
namespace ProductKeywordBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

class ProductKeywordBundle extends Bundle
{
    public function getPath(): string
    {
        return dirname(__DIR__);
    }
}
```

### 5.2 服务配置

```yaml
# config/services.yaml
services:
    ProductKeywordBundle\Interface\KeywordManagerInterface:
        class: ProductKeywordBundle\Service\KeywordManager
        
    ProductKeywordBundle\Interface\SearchLoggerInterface:
        class: ProductKeywordBundle\Service\SearchLogger
        
    ProductKeywordBundle\Interface\SearchAnalyzerInterface:
        class: ProductKeywordBundle\Service\SearchAnalyzer
        
    ProductKeywordBundle\Interface\SearchOptimizerInterface:
        class: ProductKeywordBundle\Service\SearchOptimizer
```

### 5.3 缓存集成

```php
class SearchAnalyzer
{
    private ?CacheInterface $cache = null;
    
    public function __construct(
        private readonly SearchLogRepository $repository
    ) {
        // 可选的 Redis 缓存
        if (isset($_ENV['REDIS_URL'])) {
            $this->cache = new RedisAdapter(
                RedisAdapter::createConnection($_ENV['REDIS_URL'])
            );
        }
    }
    
    public function analyzeHotKeywords(DateRange $range, int $limit): array
    {
        $cacheKey = sprintf('hot_keywords_%s_%s_%d', 
            $range->getStart()->format('Ymd'),
            $range->getEnd()->format('Ymd'),
            $limit
        );
        
        if ($this->cache) {
            $cached = $this->cache->get($cacheKey, function() use ($range, $limit) {
                return $this->doAnalyze($range, $limit);
            });
            return $cached;
        }
        
        return $this->doAnalyze($range, $limit);
    }
}
```

## 6. 测试策略

### 6.1 单元测试

```php
class KeywordManagerTest extends TestCase
{
    public function testCreateKeyword(): void
    {
        $repository = $this->createMock(KeywordRepository::class);
        $validator = $this->createMock(KeywordValidator::class);
        $eventDispatcher = $this->createMock(EventDispatcherInterface::class);
        
        $manager = new KeywordManager($repository, $validator, $eventDispatcher);
        
        $dto = new KeywordDTO('手机', 10.0, null, true, false);
        $keyword = $manager->create($dto);
        
        $this->assertEquals('手机', $keyword->getKeyword());
        $this->assertEquals(10.0, $keyword->getWeight());
    }
}
```

### 6.2 集成测试

```php
class SearchLoggerIntegrationTest extends KernelTestCase
{
    public function testAsyncLogging(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        
        $logger = $container->get(SearchLoggerInterface::class);
        $logger->logAsync(new SearchLogDTO(
            keyword: '测试关键词',
            userId: 'test123',
            resultCount: 10,
            source: 'test',
            sessionId: 'session123'
        ));
        
        // 验证消息已发送到队列
        $transport = $container->get('messenger.transport.async');
        $this->assertCount(1, $transport->get());
    }
}
```

### 6.3 性能测试

```php
class PerformanceBenchmark
{
    public function benchmarkSearchLogging(): void
    {
        $logger = new SearchLogger($repository, $messageBus);
        
        $startTime = microtime(true);
        
        for ($i = 0; $i < 1000; $i++) {
            $logger->logAsync(new SearchLogDTO(
                keyword: 'keyword_' . $i,
                userId: 'user_' . $i,
                resultCount: rand(0, 1000),
                source: 'benchmark',
                sessionId: 'session_' . $i
            ));
        }
        
        $duration = microtime(true) - $startTime;
        $qps = 1000 / $duration;
        
        $this->assertGreaterThan(1000, $qps); // 验证 QPS > 1000
    }
}
```

## 7. 性能优化策略

### 7.1 数据库优化
- 合理的索引设计（keyword、user_hash、create_time）
- 分区表设计（按月分区搜索日志）
- 读写分离（主从复制）

### 7.2 缓存策略
- 热门关键词缓存（TTL: 1小时）
- 搜索推荐缓存（TTL: 30分钟）
- 分析报表缓存（TTL: 6小时）

### 7.3 异步处理
- 搜索记录异步写入
- 批量处理优化
- 定时任务归档

## 8. 安全考虑

### 8.1 输入验证
```php
class KeywordValidator
{
    public function validate(string $keyword): void
    {
        // 长度验证
        if (strlen($keyword) < 1 || strlen($keyword) > 100) {
            throw new InvalidKeywordException('关键词长度必须在1-100之间');
        }
        
        // 特殊字符过滤
        if (preg_match('/[<>"\']/', $keyword)) {
            throw new InvalidKeywordException('关键词包含非法字符');
        }
        
        // SQL注入防护（使用参数化查询）
        // XSS防护（输出时转义）
    }
}
```

### 8.2 隐私保护
```php
class SearchLogger
{
    private function anonymizeUser(string $userId): string
    {
        // 使用单向哈希保护用户隐私
        return hash('sha256', $userId . ($_ENV['USER_SALT'] ?? 'default'));
    }
}
```

### 8.3 访问控制
- API 限流（Rate Limiting）
- 认证授权（JWT/OAuth）
- 审计日志

## 9. 错误处理

### 9.1 异常层次
```
KeywordException
├── DuplicateKeywordException
├── InvalidKeywordException
├── KeywordNotFoundException
└── SearchLogException
```

### 9.2 错误恢复
```php
class SearchLogger
{
    public function logAsync(SearchLogDTO $dto): void
    {
        try {
            $this->messageBus->dispatch(new LogSearchMessage($dto));
        } catch (\Exception $e) {
            // 降级到同步写入
            $this->log($dto);
            
            // 记录错误
            $this->logger->error('异步写入失败，降级到同步', [
                'error' => $e->getMessage()
            ]);
        }
    }
}
```

## 10. 监控和日志

### 10.1 关键指标
- 搜索 QPS
- 平均响应时间
- 无结果搜索率
- 缓存命中率

### 10.2 日志策略
```php
class KeywordManager
{
    private readonly LoggerInterface $logger;
    
    public function create(KeywordDTO $dto): Keyword
    {
        $this->logger->info('创建关键词', [
            'keyword' => $dto->keyword,
            'weight' => $dto->weight
        ]);
        
        // 业务逻辑...
    }
}
```

## 11. 部署考虑

### 11.1 环境变量
```bash
# 必需配置
DATABASE_URL=mysql://user:pass@localhost/db

# 可选配置
KEYWORD_ASYNC_LOG=true
KEYWORD_LOG_BATCH_SIZE=100
REDIS_URL=redis://localhost:6379
MESSENGER_TRANSPORT_DSN=redis://localhost:6379/messages
```

### 11.2 依赖要求
- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 2.0+
- Redis（可选，用于缓存）
- RabbitMQ/Redis（可选，用于队列）

## 12. 设计验证

### 12.1 需求覆盖验证

| 需求 | 设计方案 | 状态 |
|------|---------|------|
| 关键词CRUD | KeywordManager Service | ✅ |
| 层级结构 | parent 自关联 | ✅ |
| 高并发写入 | 异步消息队列 | ✅ |
| 搜索分析 | SearchAnalyzer Service | ✅ |
| 关键词推荐 | SearchOptimizer Service | ✅ |
| 隐私保护 | 用户哈希匿名化 | ✅ |
| 性能要求 | 缓存+异步+索引 | ✅ |

### 12.2 架构合规性检查

- ✅ 不使用 DDD 分层架构
- ✅ 不创建值对象目录
- ✅ 不使用富领域模型
- ✅ 使用扁平化的 Service 层
- ✅ 遵循 `.claude/standards/symfony-bundle-standards.md`
- ✅ 不创建 Configuration 类
- ✅ 不主动创建 HTTP API 端点
- ✅ 实体是贫血模型
- ✅ 业务逻辑在 Service 中
- ✅ 使用构造函数注入
- ✅ 不创建不必要的抽象