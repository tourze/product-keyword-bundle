<?php

namespace ProductKeywordBundle\DependencyInjection;

use Tourze\SymfonyDependencyServiceLoader\AutoExtension;

final class ProductKeywordExtension extends AutoExtension
{
    protected function getConfigDir(): string
    {
        return __DIR__ . '/../Resources/config';
    }

    public function getAlias(): string
    {
        return 'product_keyword';
    }
}
