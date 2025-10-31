# 商品关键词管理 Bundle

[English](README.md) | [中文](README.zh-CN.md)

[![Latest Version](https://img.shields.io/packagist/v/tourze/product-keyword-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/product-keyword-bundle)
[![Total Downloads](https://img.shields.io/packagist/dt/tourze/product-keyword-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/product-keyword-bundle)
[![PHP Version](https://img.shields.io/packagist/php-v/tourze/product-keyword-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/product-keyword-bundle)
[![License](https://img.shields.io/packagist/l/tourze/product-keyword-bundle.svg?style=flat-square)](https://packagist.org/packages/tourze/product-keyword-bundle)
[![Build Status](https://img.shields.io/github/workflow/status/tourze/product-keyword-bundle/CI.svg?style=flat-square)](https://github.com/tourze/product-keyword-bundle/actions)
[![Code Coverage](https://img.shields.io/codecov/c/github/tourze/product-keyword-bundle.svg?style=flat-square)](https://codecov.io/gh/tourze/product-keyword-bundle)

一个用于管理商品关键词、支持商品搜索和关键词分类的 Symfony Bundle。

## 目录

- [核心功能](#核心功能)
- [安装](#安装)
- [配置](#配置)
- [核心实体](#核心实体)
- [使用方法](#使用方法)
- [高级用法](#高级用法)
- [数据库表结构](#数据库表结构)
- [权重计算机制](#权重计算机制)
- [技术特性](#技术特性)
- [依赖要求](#依赖要求)
- [扩展建议](#扩展建议)
- [贡献指南](#贡献指南)
- [许可证](#许可证)

## 核心功能

- **关键词管理**: 创建、编辑、删除商品关键词
- **关键词分类**: 支持对关键词进行分类管理
- **权重系统**: 每个关键词和商品关键词关联都支持权重设置
- **关键词搜索**: 基于关键词查找相关商品，按权重排序
- **EasyAdmin 集成**: 提供完整的后台管理界面
- **推荐机制**: 支持关键词推荐标记

## 安装

```bash
composer require tourze/product-keyword-bundle
```

## 配置

在 `config/bundles.php` 中注册 Bundle：

```php
<?php

return [
    // ...
    ProductKeywordBundle\ProductKeywordBundle::class => ['all' => true],
];
```

## 核心实体

### Keyword (关键词)

```yaml
- id: 雪花算法生成的唯一ID
- keyword: 关键词文本（最大100字符，唯一）
- category: 关键词分类（可选）
- weight: 权重值（默认1.0）
- thumb: 缩略图（可选）
- description: 描述（可选）
- valid: 是否有效（默认false）
- recommend: 是否推荐（默认false）
```

### KeywordCategory (关键词分类)

```yaml
- id: 雪花算法生成的唯一ID
- name: 分类名称
- description: 分类描述
```

### ProductKeyword (商品关键词关联)

```yaml
- id: 雪花算法生成的唯一ID
- keyword: 关联的关键词
- spuId: SPU ID
- weight: 关联权重（默认1.0）
- source: 关键词来源（默认'manual'）
```

## 使用方法

### 1. 关键词搜索服务

```php
use ProductKeywordBundle\Service\KeywordSearchService;

public function __construct(
    private KeywordSearchService $keywordSearchService
) {}

// 根据单个关键词查找商品
$products = $this->keywordSearchService->findProductsByKeyword('手机');

// 根据多个关键词查找商品
$products = $this->keywordSearchService->findProductsByKeywords(['手机', '苹果']);

// 返回格式：
// [
//     ['productId' => 123, 'weight' => 2.5],
//     ['productId' => 456, 'weight' => 1.8],
// ]
```

### 2. 仓储服务

```php
use ProductKeywordBundle\Repository\KeywordRepository;
use ProductKeywordBundle\Repository\KeywordCategoryRepository;
use ProductKeywordBundle\Repository\ProductKeywordRepository;

// 查找有效关键词
$keyword = $keywordRepository->findByKeyword('手机');

// 根据名称查找多个有效关键词
$keywords = $keywordRepository->findValidKeywordsByNames(['手机', '苹果']);

// 查找推荐关键词
$recommendKeywords = $keywordRepository->findRecommendedKeywords();
```

### 3. EasyAdmin 后台管理

Bundle 自动提供以下管理界面：

- `ProductKeywordKeywordCrudController`: 关键词管理
- `ProductKeywordKeywordCategoryCrudController`: 关键词分类管理  
- `ProductKeywordProductKeywordCrudController`: 商品关键词关联管理

## 高级用法

### 自定义关键词过滤

```php
use ProductKeywordBundle\Repository\KeywordRepository;

public function getFilteredKeywords(
    KeywordRepository $keywordRepository,
    ?string $category = null,
    ?float $minWeight = null
): array {
    $qb = $keywordRepository->createQueryBuilder('k')
        ->where('k.valid = :valid')
        ->setParameter('valid', true);
    
    if ($category !== null) {
        $qb->andWhere('k.category = :category')
           ->setParameter('category', $category);
    }
    
    if ($minWeight !== null) {
        $qb->andWhere('k.weight >= :minWeight')
           ->setParameter('minWeight', $minWeight);
    }
    
    return $qb->getQuery()->getResult();
}
```

### 批量商品关键词关联

```php
use ProductKeywordBundle\Entity\ProductKeyword;
use ProductKeywordBundle\Repository\KeywordRepository;
use Doctrine\ORM\EntityManagerInterface;

public function batchAssociateKeywords(
    EntityManagerInterface $em,
    KeywordRepository $keywordRepository,
    string $spuId,
    array $keywordNames,
    string $source = 'auto'
): void {
    $keywords = $keywordRepository->findValidKeywordsByNames($keywordNames);
    
    foreach ($keywords as $keyword) {
        $productKeyword = new ProductKeyword();
        $productKeyword->setSpuId($spuId)
                      ->setKeyword($keyword)
                      ->setSource($source)
                      ->setWeight($keyword->getWeight());
        
        $em->persist($productKeyword);
    }
    
    $em->flush();
}
```

### 带过滤条件的自定义搜索

```php
use ProductKeywordBundle\Service\KeywordSearchService;

public function searchWithFilters(
    KeywordSearchService $searchService,
    array $keywords,
    ?string $source = null,
    ?float $minWeight = null
): array {
    $results = $searchService->findProductsByKeywords($keywords);
    
    if ($source !== null || $minWeight !== null) {
        return array_filter($results, function($result) use ($source, $minWeight) {
            if ($source !== null && $result['source'] !== $source) {
                return false;
            }
            if ($minWeight !== null && $result['weight'] < $minWeight) {
                return false;
            }
            return true;
        });
    }
    
    return $results;
}
```

## 数据库表结构

```sql
-- 关键词表
CREATE TABLE product_keyword (
    id BIGINT PRIMARY KEY COMMENT '雪花算法ID',
    keyword VARCHAR(100) UNIQUE NOT NULL COMMENT '关键词',
    category_id BIGINT NULL COMMENT '分类ID',
    weight FLOAT DEFAULT 1.0 COMMENT '权重值',
    thumb VARCHAR(255) NULL COMMENT '缩略图',
    description TEXT NULL COMMENT '描述',
    valid BOOLEAN DEFAULT 0 COMMENT '有效',
    recommend BOOLEAN DEFAULT 0 COMMENT '是否推荐',
    created_at DATETIME COMMENT '创建时间',
    updated_at DATETIME COMMENT '更新时间',
    created_by VARCHAR(255) COMMENT '创建者',
    updated_by VARCHAR(255) COMMENT '更新者'
);

-- 关键词分类表
CREATE TABLE product_keyword_category (
    id BIGINT PRIMARY KEY COMMENT '雪花算法ID',
    name VARCHAR(100) NOT NULL COMMENT '分类名称',
    description TEXT NULL COMMENT '分类描述',
    created_at DATETIME COMMENT '创建时间',
    updated_at DATETIME COMMENT '更新时间'
);

-- 商品关键词关联表
CREATE TABLE product_keyword_relation (
    id BIGINT PRIMARY KEY COMMENT '雪花算法ID',
    keyword_id BIGINT NOT NULL COMMENT '关键词ID',
    spu_id VARCHAR(255) NOT NULL COMMENT 'SPU ID',
    weight FLOAT DEFAULT 1.0 COMMENT '关联权重',
    source VARCHAR(20) DEFAULT 'manual' COMMENT '来源',
    created_at DATETIME COMMENT '创建时间',
    updated_at DATETIME COMMENT '更新时间',
    created_by VARCHAR(255) COMMENT '创建者',
    updated_by VARCHAR(255) COMMENT '更新者'
);
```

## 权重计算机制

搜索时的最终权重计算公式：

```text
最终权重 = 关键词权重 × 商品关键词关联权重
```

## 技术特性

- **雪花算法ID**: 使用雪花算法生成分布式唯一ID
- **时间戳追踪**: 自动记录创建和更新时间
- **用户追踪**: 自动记录创建者和更新者
- **索引优化**: 关键字段已添加数据库索引
- **数据验证**: 使用 Symfony Validator 进行数据验证
- **数组转换**: 支持 API、Admin、Plain 多种数组输出格式

## 依赖要求

- PHP 8.1+
- Symfony 6.4+
- Doctrine ORM 3.0+
- EasyAdmin Bundle 4+

## 扩展建议

1. **缓存优化**: 可集成 Redis 缓存热门关键词查询结果
2. **搜索引擎**: 可结合 Elasticsearch 实现更强大的全文搜索
3. **统计分析**: 可添加关键词搜索统计功能
4. **自动补全**: 可基于关键词数据实现搜索自动补全
5. **同义词支持**: 可扩展同义词匹配功能

## 贡献指南

请查看我们的[贡献指南](../../CONTRIBUTING.md)了解如何为该项目做出贡献。

## 许可证

MIT 许可证。请查看 [License 文件](LICENSE) 获取更多信息。
