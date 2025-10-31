<?php

declare(strict_types=1);

namespace ProductKeywordBundle\Tests;

use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use ProductKeywordBundle\ProductKeywordBundle;
use Tourze\PHPUnitSymfonyKernelTest\AbstractBundleTestCase;

/**
 * @internal
 */
#[CoversClass(ProductKeywordBundle::class)]
#[RunTestsInSeparateProcesses]
final class ProductKeywordBundleTest extends AbstractBundleTestCase
{
}
