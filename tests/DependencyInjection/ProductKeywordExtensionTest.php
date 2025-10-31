<?php

namespace ProductKeywordBundle\Tests\DependencyInjection;

use PHPUnit\Framework\Attributes\CoversClass;
use ProductKeywordBundle\DependencyInjection\ProductKeywordExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Tourze\PHPUnitSymfonyUnitTest\AbstractDependencyInjectionExtensionTestCase;

/**
 * @internal
 */
#[CoversClass(ProductKeywordExtension::class)]
final class ProductKeywordExtensionTest extends AbstractDependencyInjectionExtensionTestCase
{
    public function testLoad(): void
    {
        $extension = new ProductKeywordExtension();
        $container = new ContainerBuilder();
        $container->setParameter('kernel.environment', 'test');

        $extension->load([], $container);

        $this->assertNotEmpty($container->getDefinitions());
    }
}
