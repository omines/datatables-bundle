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

use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableFactory;
use Omines\DataTablesBundle\DataTableRendererInterface;
use Omines\DataTablesBundle\DataTablesBundle;
use Omines\DataTablesBundle\DependencyInjection\DataTablesExtension;
use Omines\DataTablesBundle\DependencyInjection\Instantiator;
use Omines\DataTablesBundle\Exception\InvalidArgumentException;
use Omines\DataTablesBundle\Exception\InvalidConfigurationException;
use Omines\DataTablesBundle\Exception\InvalidStateException;
use Omines\DataTablesBundle\Exporter\DataTableExporterManager;
use Omines\DataTablesBundle\Twig\TwigRenderer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Tests\Fixtures\AppBundle\DataTable\Type\RegularPersonTableType;

/**
 * DataTableTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class DataTableTest extends TestCase
{
    public function testBundle(): void
    {
        $bundle = new DataTablesBundle();
        $this->assertSame('DataTablesBundle', $bundle->getName());
    }

    public function testFactory(): void
    {
        $factory = new DataTableFactory(['language_from_cdn' => false], $this->createMock(TwigRenderer::class), new Instantiator(), $this->createMock(EventDispatcher::class), $this->createMock(DataTableExporterManager::class));

        $table = $factory->create(['pageLength' => 684, 'dom' => 'bar']);
        $this->assertSame(684, $table->getOption('pageLength'));
        $this->assertSame('bar', $table->getOption('dom'));
        $this->assertFalse($table->isLanguageFromCDN());
        $this->assertNull($table->getOption('invalid'));

        $table->setAdapter(new ArrayAdapter());
        $this->assertInstanceOf(ArrayAdapter::class, $table->getAdapter());
    }

    public function testFactoryRemembersInstances(): void
    {
        $factory = new DataTableFactory([], $this->createMock(TwigRenderer::class), new Instantiator(), $this->createMock(EventDispatcher::class), $this->createMock(DataTableExporterManager::class));

        $reflection = new \ReflectionClass(DataTableFactory::class);
        $property = $reflection->getProperty('resolvedTypes');
        $property->setAccessible(true);

        $this->assertEmpty($property->getValue($factory));
        $factory->createFromType(RegularPersonTableType::class);
        $factory->createFromType(RegularPersonTableType::class);
        $this->assertCount(1, $property->getValue($factory));
    }

    public function testDataTableState(): void
    {
        $datatable = $this->createMockDataTable();
        $datatable
            ->add('foo', TextColumn::class)
            ->add('bar', TextColumn::class)
            ->setMethod(Request::METHOD_GET);
        $datatable->handleRequest(Request::create('/?_dt=' . $datatable->getName()));
        $state = $datatable->getState();

        // Test sane defaults
        $this->assertSame(0, $state->getStart());
        $this->assertSame(10, $state->getLength());
        $this->assertSame(0, $state->getDraw());
        $this->assertSame('', $state->getGlobalSearch());
        $this->assertFalse($state->isExport());

        $state->setStart(5);
        $state->setLength(10);
        $state->setGlobalSearch('foo');
        $state->setOrderBy([
            [$datatable->getColumn(0), 'asc'],
            [$datatable->getColumn(1), 'desc'],
        ]);
        $state->setColumnSearch($datatable->getColumn(0), 'bar');

        $this->assertSame(5, $state->getStart());
        $this->assertSame(10, $state->getLength());
        $this->assertSame('foo', $state->getGlobalSearch());
        $this->assertCount(2, $state->getOrderBy());
        $this->assertSame('bar', $state->getSearchColumns(onlySearchable: false)['foo']['search']);

        // Test boundaries
        $state->setStart(-1);
        $state->setLength(0);

        $this->assertSame(0, $state->getStart());
        $this->assertNull($state->getLength());

        $column = $datatable->getColumn(0);
        $this->assertSame($state, $column->getState());
    }

    /**
     * Tests that getSearchColumns only returns columns for which `isSearchable()` is true.
     */
    public function testDataTableStateSearchColumns(): void
    {
        $datatable = $this
            ->createMockDataTable()
            ->add('foo', TextColumn::class, ['searchable' => true])
            ->add('bar', TextColumn::class, ['searchable' => false])
            ->setMethod(Request::METHOD_GET)
        ;
        $datatable->handleRequest(Request::create('/?_dt=' . $datatable->getName()));

        $state = $datatable->getState();
        $state->setColumnSearch($datatable->getColumn(0), 'foo');
        $state->setColumnSearch($datatable->getColumn(1), 'bar');

        $searchColumns = $state->getSearchColumns();
        $this->assertCount(1, $searchColumns);
        $this->assertSame('foo', $searchColumns['foo']['search']);
    }

    public function testSortDirectionValidation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('direction must be one of');

        $datatable = $this
            ->createMockDataTable()
            ->add('foo', TextColumn::class, ['searchable' => true])
        ;
        $datatable->handleRequest(Request::create('/foo', Request::METHOD_POST, ['_dt' => $datatable->getName(), 'draw' => 684]));
        $datatable->getState()->addOrderBy($datatable->getColumn(0), 'foo');
    }

    public function testInvalidSortParametersAreIgnored(): void
    {
        $datatable = $this
            ->createMockDataTable()
            ->add('foo', TextColumn::class, ['searchable' => true])
        ;
        $datatable->handleRequest(Request::create('/foo', Request::METHOD_POST, [
            '_dt' => $datatable->getName(),
            'draw' => 684,
            'order' => [[
                'column' => 0,
                'dir' => 'foo',
            ]],
        ]));
        $this->assertEmpty($datatable->getState()->getOrderBy());
    }

    public function testPostMethod(): void
    {
        $datatable = $this->createMockDataTable();
        $datatable->handleRequest(Request::create('/foo', Request::METHOD_POST, ['_dt' => $datatable->getName(), 'draw' => 684]));

        $this->assertSame(684, $datatable->getState()->getDraw());
    }

    public function testFactoryFailsOnInvalidType(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Could not resolve type');

        $dummy = new ServiceLocator([]);
        $container = new ContainerBuilder();
        (new DataTablesExtension())->load([], $container);

        $factory = new DataTableFactory($container->getParameter('datatables.config'), $this->createMock(TwigRenderer::class), new Instantiator(), $this->createMock(EventDispatcher::class), $this->createMock(DataTableExporterManager::class));
        $factory->createFromType('foobar');
    }

    public function testInvalidOption(): void
    {
        $this->expectException(UndefinedOptionsException::class);

        $this->createMockDataTable(['option' => 'bar']);
    }

    public function testDataTableInvalidColumn(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createMockDataTable()->getColumn(5);
    }

    public function testDataTableInvalidColumnByName(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->createMockDataTable()->getColumnByName('foo');
    }

    public function testDuplicateColumnNameThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('There already is a column with name');

        $this->createMockDataTable()
            ->add('foo', TextColumn::class)
            ->add('foo', TextColumn::class)
        ;
    }

    public function testInvalidAdapterThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not resolve type "foo\\bar" to a service or class, are you missing a use statement? Or is it implemented but does it not correctly derive from "Omines\\DataTablesBundle\\Adapter\\AdapterInterface"?');

        $this->createMockDataTable()->createAdapter('foo\bar');
    }

    public function testInvalidColumnThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not resolve type "bar" to a service or class, are you missing a use statement? Or is it implemented but does it not correctly derive from "Omines\\DataTablesBundle\\Column\\AbstractColumn"?');

        $this->createMockDataTable()->add('foo', 'bar');
    }

    public function testMissingAdapterThrows(): void
    {
        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('No adapter was configured yet to retrieve data with');
        $datatable = $this->createMockDataTable();
        $datatable
            ->setMethod(Request::METHOD_GET)
            ->handleRequest(Request::create('/?_dt=' . $datatable->getName()))
            ->getResponse()
        ;
    }

    public function testEmptyNameThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('DataTable name cannot be empty');

        $this->createMockDataTable()->setName('');
    }

    public function testStateWillNotProcessInvalidMethod(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage("Unknown request method 'OPTIONS'");

        $datatable = $this->createMockDataTable();
        $datatable->setMethod(Request::METHOD_OPTIONS);
        $datatable->handleRequest(Request::create('/foo'));
    }

    public function testMissingStateThrows(): void
    {
        $this->expectException(InvalidStateException::class);
        $this->expectExceptionMessage('The DataTable does not know its state yet');

        $this->createMockDataTable()->getResponse();
    }

    public function testInvalidDataTableTypeThrows(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Could not resolve type "foo" to a service or class');

        (new DataTableFactory([], $this->createMock(DataTableRendererInterface::class), new Instantiator(), $this->createMock(EventDispatcher::class), $this->createMock(DataTableExporterManager::class)))
            ->createFromType('foo');
    }

    private function createMockDataTable(array $options = []): DataTable
    {
        return new DataTable($this->createMock(EventDispatcher::class), $this->createMock(DataTableExporterManager::class), $options);
    }
}
