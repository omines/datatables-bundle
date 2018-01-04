<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);
/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit;

use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableFactory;
use Omines\DataTablesBundle\DataTableRendererInterface;
use Omines\DataTablesBundle\DataTablesBundle;
use Omines\DataTablesBundle\DependencyInjection\DataTablesExtension;
use Omines\DataTablesBundle\DependencyInjection\Instantiator;
use Omines\DataTablesBundle\Twig\TwigRenderer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\HttpFoundation\Request;
use Tests\Fixtures\AppBundle\DataTable\Type\RegularPersonTableType;

/**
 * DataTableTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class DataTableTest extends TestCase
{
    public function testBundle()
    {
        $bundle = new DataTablesBundle();
        $this->assertSame('DataTablesBundle', $bundle->getName());
    }

    public function testFactory()
    {
        $factory = new DataTableFactory(['language_from_cdn' => false], $this->createMock(TwigRenderer::class), new Instantiator());

        $table = $factory->create(['pageLength' => 684, 'dom' => 'bar']);
        $this->assertSame(684, $table->getOption('pageLength'));
        $this->assertSame('bar', $table->getOption('dom'));
        $this->assertFalse($table->isLanguageFromCDN());
        $this->assertNull($table->getOption('invalid'));

        $table->setAdapter(new ArrayAdapter());
        $this->assertInstanceOf(ArrayAdapter::class, $table->getAdapter());
    }

    public function testFactoryRemembersInstances()
    {
        $factory = new DataTableFactory([], $this->createMock(TwigRenderer::class), new Instantiator());

        $reflection = new \ReflectionClass(DataTableFactory::class);
        $property = $reflection->getProperty('resolvedTypes');
        $property->setAccessible(true);

        $this->assertEmpty($property->getValue($factory));
        $factory->createFromType(RegularPersonTableType::class);
        $factory->createFromType(RegularPersonTableType::class);
        $this->assertCount(1, $property->getValue($factory));
    }

    public function testDataTableState()
    {
        $datatable = new DataTable();
        $datatable->add('foo', TextColumn::class)->setMethod(Request::METHOD_GET);
        $datatable->handleRequest(Request::create('/?_dt=' . $datatable->getName()));
        $state = $datatable->getState();

        // Test sane defaults
        $this->assertSame(0, $state->getStart());
        $this->assertSame(10, $state->getLength());
        $this->assertSame(0, $state->getDraw());
        $this->assertSame('', $state->getGlobalSearch());

        $state->setStart(5);
        $state->setLength(10);
        $state->setGlobalSearch('foo');
        $state->setOrderBy([[0, 'asc'], [1, 'desc']]);
        $state->setColumnSearch($datatable->getColumn(0), 'bar');

        $this->assertSame(5, $state->getStart());
        $this->assertSame(10, $state->getLength());
        $this->assertSame('foo', $state->getGlobalSearch());
        $this->assertCount(2, $state->getOrderBy());
        $this->assertSame('bar', $state->getSearchColumns()['foo']['search']);
    }

    public function testPostMethod()
    {
        $datatable = new DataTable();
        $datatable->handleRequest(Request::create('/foo', Request::METHOD_POST, ['_dt' => $datatable->getName(), 'draw' => 684]));

        $this->assertSame(684, $datatable->getState()->getDraw());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Could not resolve type
     */
    public function testFactoryFailsOnInvalidType()
    {
        $dummy = new ServiceLocator([]);
        $container = new ContainerBuilder();
        (new DataTablesExtension())->load([], $container);

        $factory = new DataTableFactory($container->getParameter('datatables.config'), $this->createMock(TwigRenderer::class), new Instantiator());
        $factory->createFromType('foobar');
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function testInvalidOption()
    {
        new DataTable(['option' => 'bar']);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDataTableInvalidColumn()
    {
        (new DataTable())->getColumn(5);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDataTableInvalidColumnByName()
    {
        (new DataTable())->getColumnByName('foo');
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage There already is a column with name
     */
    public function testDuplicateColumnNameThrows()
    {
        (new DataTable())
            ->add('foo', TextColumn::class)
            ->add('foo', TextColumn::class)
        ;
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Could not resolve type "foo\bar" to a service or class, are you missing a use statement? Or is it implemented but does it not correctly derive from "Omines\DataTablesBundle\Adapter\AdapterInterface"?
     */
    public function testInvalidAdapterThrows()
    {
        (new DataTable())
            ->createAdapter('foo\bar')
        ;
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Could not resolve type "bar" to a service or class, are you missing a use statement? Or is it implemented but does it not correctly derive from "Omines\DataTablesBundle\Column\AbstractColumn"?
     */
    public function testInvalidColumnThrows()
    {
        (new DataTable())
            ->add('foo', 'bar');
    }

    /**
     * @expectedException \Omines\DataTablesBundle\Exception\InvalidStateException
     * @expectedExceptionMessage No adapter was configured yet to retrieve data with
     */
    public function testMissingAdapterThrows()
    {
        $datatable = new DataTable();
        $datatable
            ->setMethod(Request::METHOD_GET)
            ->handleRequest(Request::create('/?_dt=' . $datatable->getName()))
            ->getResponse()
        ;
    }

    /**
     * @expectedException \Omines\DataTablesBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage DataTable name cannot be empty
     */
    public function testEmptyNameThrows()
    {
        (new DataTable())->setName('');
    }

    /**
     * @expectedException \Omines\DataTablesBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Unknown request method 'OPTIONS'
     */
    public function testStateWillNotProcessInvalidMethod()
    {
        $datatable = new DataTable();
        $datatable->setMethod(Request::METHOD_OPTIONS);
        $datatable->handleRequest(Request::create('/foo'));
    }

    /**
     * @expectedException \Omines\DataTablesBundle\Exception\InvalidStateException
     * @expectedExceptionMessage The DataTable does not know its state yet
     */
    public function testMissingStateThrows()
    {
        (new DataTable())
            ->getResponse();
    }

    /**
     * @expectedException \Omines\DataTablesBundle\Exception\InvalidArgumentException
     * @expectedExceptionMessage Could not resolve type "foo" to a service or class
     */
    public function testInvalidDataTableTypeThrows()
    {
        (new DataTableFactory([], $this->createMock(DataTableRendererInterface::class), new Instantiator()))
            ->createFromType('foo');
    }
}
