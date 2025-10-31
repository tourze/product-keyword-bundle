# Product Keyword Bundle - 后台管理功能

## 功能概述

Product Keyword Bundle 提供了完整的商品关键词后台管理功能，包括关键词管理、层级结构、权重设置、推荐管理以及商品与关键词的关联管理。

## 管理功能

### 1. 关键词管理

**路由**: `/admin/product-keyword/keyword`

#### 功能特性

- **关键词列表展示**
  - 显示所有关键词信息
  - 支持按关键词名称、描述等条件搜索
  - 支持多条件筛选（有效状态、推荐状态、权重等）
  - 显示父级关键词信息
  - 显示关联商品数量

- **层级结构管理**
  - 支持设置父级关键词
  - 建立关键词分类树
  - 方便的层级导航

- **状态管理**
  - 启用/禁用关键词
  - 设为推荐/取消推荐
  - 批量状态更新

- **权重设置**
  - 设置关键词权重（0-100）
  - 影响搜索排序
  - 默认权重为 1.0

- **详细信息**
  - 关键词缩略图上传
  - 关键词描述编辑
  - 查看创建和更新信息

#### 自定义操作

- **启用关键词**: 将关键词状态设为有效
- **禁用关键词**: 将关键词状态设为无效
- **设为推荐**: 标记为推荐关键词
- **取消推荐**: 取消推荐标记

### 2. 商品关键词关联管理

**路由**: `/admin/product-keyword/relation`

#### 功能特性

- **关联关系管理**
  - 创建商品与关键词的关联
  - 设置关联权重（0-10）
  - 管理关联来源

- **商品SPU关联**
  - 通过 SPU ID 关联商品
  - 一个商品可关联多个关键词
  - 支持唯一性约束（同一商品不能重复关联同一关键词）

- **权重管理**
  - 独立的关联权重设置
  - 与关键词本身权重分离
  - 影响搜索相关性

- **来源追踪**
  - **manual**: 手动添加
  - **auto**: 自动生成
  - **import**: 批量导入

- **数据展示**
  - 显示关键词信息
  - 显示关键词权重
  - 显示关键词父级
  - 显示创建和更新信息

## 菜单结构

管理菜单会自动注册到 EasyAdmin 后台：

```
商品管理
└── 关键词管理
    ├── 关键词列表
    └── 商品关键词
```

## 数据模型

### Keyword 实体

| 字段 | 类型 | 说明 |
|-----|------|------|
| id | bigint | 主键（雪花ID） |
| keyword | string(100) | 关键词名称（唯一） |
| parent | Keyword | 父级关键词 |
| weight | float | 权重值（0-100） |
| thumb | string(255) | 缩略图URL |
| description | text | 描述信息 |
| valid | boolean | 是否有效 |
| recommend | boolean | 是否推荐 |
| createTime | datetime | 创建时间 |
| updateTime | datetime | 更新时间 |
| createdBy | User | 创建人 |
| updatedBy | User | 更新人 |

### ProductKeyword 实体

| 字段 | 类型 | 说明 |
|-----|------|------|
| id | int | 主键（自增） |
| spuId | string(50) | 商品SPU ID |
| keyword | Keyword | 关联的关键词 |
| weight | float | 权重值（0-10） |
| source | string(20) | 来源（manual/auto/import） |
| createTime | datetime | 创建时间 |
| updateTime | datetime | 更新时间 |
| createdBy | User | 创建人 |
| updatedBy | User | 更新人 |

## 筛选器

### 关键词筛选器

- **ID**: 按关键词ID搜索
- **关键词**: 按关键词名称搜索
- **父级关键词**: 按父级筛选
- **权重值**: 按权重范围筛选
- **是否有效**: 筛选有效/无效关键词
- **是否推荐**: 筛选推荐关键词
- **创建时间**: 按时间范围筛选
- **描述**: 按描述内容搜索

### 商品关键词筛选器

- **ID**: 按记录ID搜索
- **商品SPU**: 按SPU ID搜索
- **关键词**: 按关联的关键词筛选
- **权重值**: 按权重范围筛选
- **来源**: 按来源类型筛选
- **创建时间**: 按时间范围筛选

## 性能优化

### 查询优化

控制器通过重写 `createIndexQueryBuilder` 方法优化了查询性能：

```php
// KeywordCrudController
public function createIndexQueryBuilder(...): QueryBuilder
{
    return parent::createIndexQueryBuilder(...)
        ->select('entity', 'parent')
        ->leftJoin('entity.parent', 'parent')
        ->orderBy('entity.id', 'DESC');
}

// ProductKeywordCrudController
public function createIndexQueryBuilder(...): QueryBuilder
{
    return parent::createIndexQueryBuilder(...)
        ->select('entity', 'keyword')
        ->leftJoin('entity.keyword', 'keyword')
        ->orderBy('entity.id', 'DESC');
}
```

### 索引优化

- keyword 字段有唯一索引
- spuId 字段有索引
- (spuId, keywordId) 有唯一复合索引
- valid、recommend、weight 字段有索引用于筛选

## 使用场景

### 1. 建立关键词体系

1. 创建顶级分类关键词（如：手机、电脑、服装）
2. 创建子分类关键词，设置父级关系
3. 设置关键词权重，重要的关键词给予更高权重
4. 标记热门关键词为推荐

### 2. 商品关键词标注

1. 为商品SPU添加相关关键词
2. 设置关联权重，核心关键词给予更高权重
3. 记录来源，便于追踪和管理
4. 定期审核和优化关键词

### 3. 搜索优化

- 通过权重调整搜索结果排序
- 推荐关键词用于热门搜索展示
- 层级结构用于搜索导航
- 关联关系用于相关商品推荐

## 权限控制

建议配置以下角色：

- **ROLE_KEYWORD_VIEWER**: 查看关键词权限
- **ROLE_KEYWORD_EDITOR**: 编辑关键词权限
- **ROLE_KEYWORD_ADMIN**: 完整管理权限

## 使用建议

1. **关键词规划**：先建立完整的关键词体系，再进行商品关联
2. **权重设置**：合理设置权重，避免极端值
3. **定期维护**：定期检查无效关键词，清理无用关联
4. **批量操作**：对于大量商品，使用导入功能批量建立关联
5. **监控分析**：定期分析关键词使用情况，优化关键词体系

## 注意事项

1. 关键词名称必须唯一，创建前检查是否已存在
2. 删除关键词会级联删除所有商品关联（CASCADE）
3. 禁用关键词不会删除关联，但会影响搜索
4. 权重值会影响搜索排序，请谨慎设置
5. 推荐标记用于前端展示，数量不宜过多

## 扩展开发

如需扩展管理功能，可以：

1. 继承现有控制器添加新操作
2. 通过事件监听器扩展功能
3. 自定义字段显示格式
4. 添加批量导入导出功能
5. 集成自动标注服务

## API 集成

关键词数据可通过以下方式获取：

```php
// 获取有效的推荐关键词
$keywords = $keywordRepository->findBy([
    'valid' => true,
    'recommend' => true
]);

// 获取商品的关键词
$productKeywords = $productKeywordRepository->findBy([
    'spuId' => $spuId
]);

// 按权重排序
$keywords = $keywordRepository->createQueryBuilder('k')
    ->where('k.valid = :valid')
    ->setParameter('valid', true)
    ->orderBy('k.weight', 'DESC')
    ->getQuery()
    ->getResult();
```

## 技术栈

- **EasyAdmin Bundle**: 后台管理框架
- **Doctrine ORM**: 数据持久化
- **Symfony Form**: 表单处理
- **AdminAction**: 自定义操作路由

## 相关文档

- [EasyAdmin 官方文档](https://symfony.com/bundles/EasyAdminBundle/current/index.html)
- [关键词实体定义](src/Entity/Keyword.php)
- [商品关键词实体定义](src/Entity/ProductKeyword.php)
- [关键词搜索服务](src/Service/KeywordSearchService.php)