<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Unit;

use Omines\DataTablesBundle\Adapter\AdapterInterface;
use Omines\DataTablesBundle\DataTablesBundle;
use Omines\DataTablesBundle\DependencyInjection\Configuration;
use Omines\DataTablesBundle\DependencyInjection\Instantiator;
use Omines\DataTablesBundle\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\ArrayNode;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * DependencyInjectionTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class DependencyInjectionTest extends TestCase
{
    public function testConfiguration(): void
    {
        $config = new Configuration();
        $tree = $config->getConfigTreeBuilder()->buildTree();

        $this->assertInstanceOf(ArrayNode::class, $tree);
    }

    public function testExtension(): void
    {
        $bundle = new DataTablesBundle();
        $extension = $bundle->getContainerExtension();
        $this->assertSame('datatables', $extension->getAlias());

        $container = new ContainerBuilder();
        $extension->load([], $container);

        // Verify default config, options should be empty
        $config = $container->getParameter('datatables.config');
        $this->assertTrue($config['language_from_cdn']);
        $this->assertEmpty($config['options']);
    }

    public function testInstantiatorTypeChecks(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement/extend ' . AdapterInterface::class);

        $instantiator = new Instantiator();
        $instantiator->getAdapter(\DateTimeImmutable::class);
    }
}
