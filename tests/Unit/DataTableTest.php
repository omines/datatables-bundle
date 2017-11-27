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
use Omines\DataTablesBundle\DataTablesBundle;
use Omines\DataTablesBundle\Twig\TwigRenderer;
use PHPUnit\Framework\TestCase;
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
        $factory = new DataTableFactory(['class_name' => 'foo'], ['dom' => 'bar'], $this->createMock(TwigRenderer::class));
        $factory->setAdapterLocator(new ServiceLocator([]));

        $table = $factory->create(['name' => 'bar'], ['pageLength' => 684]);
        $this->assertSame('bar', $table->getSetting('name'));
        $this->assertSame('foo', $table->getSetting('class_name'));
        $this->assertSame('bar', $table->getOption('dom'));
        $this->assertSame(684, $table->getOption('pageLength'));
        $this->assertArrayHasKey('name', $table->getSettings());

        $table = $factory->create(['class_name' => 'bar'], ['dom' => 'foo']);
        $this->assertSame('bar', $table->getSetting('class_name'));
        $this->assertSame('foo', $table->getOption('dom'));
        $this->assertNull($table->getSetting('none'));
        $this->assertNull($table->getOption('invalid'));

        $table->setAdapter(new ArrayAdapter());
        $this->assertInstanceOf(ArrayAdapter::class, $table->getAdapter());
    }

    public function testFactoryRemembersInstances()
    {
        $factory = new DataTableFactory([], [], $this->createMock(TwigRenderer::class));
        $factory->setAdapterLocator(new ServiceLocator([]));

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
        $datatable = new DataTable(['method' => Request::METHOD_GET]);
        $datatable->add('foo', TextColumn::class);
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
        $datatable = new DataTable(['method' => Request::METHOD_POST]);
        $datatable->handleRequest(Request::create('/foo', Request::METHOD_POST, ['_dt' => $datatable->getName(), 'draw' => 684]));

        $this->assertSame(684, $datatable->getState()->getDraw());
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Could not resolve type
     */
    public function testFactoryFailsOnInvalidType()
    {
        $factory = new DataTableFactory([], [], $this->createMock(TwigRenderer::class));
        $factory->setAdapterLocator(new ServiceLocator([]));
        $factory->createFromType('foobar');
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function testInvalidSetting()
    {
        new DataTable(['setting' => 'foo'], []);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function testInvalidOption()
    {
        new DataTable([], ['option' => 'bar']);
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
     * @expectedException \LogicException
     * @expectedExceptionMessage setting is currently not supported
     */
    public function testColumnFilterIsProhibited()
    {
        (new DataTable(['column_filter' => 'thead']));
    }

    /**
     * @expectedException \RuntimeException
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
     * @expectedExceptionMessage Could not resolve adapter type
     */
    public function testInvalidAdapterThrows()
    {
        (new DataTable())
            ->createAdapter('foo\bar')
        ;
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage No adapter was configured to retrieve data
     */
    public function testMissingAdapterThrows()
    {
        $datatable = new DataTable(['method' => Request::METHOD_GET]);
        $datatable->handleRequest(Request::create('/?_dt=' . $datatable->getName()));
        $datatable->getResponse();
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage DataTable name cannot be empty
     */
    public function testEmptyNameThrows()
    {
        (new DataTable())->setName('');
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Unknown request method 'OPTIONS'
     */
    public function testStateWillNotProcessInvalidMethod()
    {
        $datatable = new DataTable();
        $reflection = new \ReflectionClass(DataTable::class);
        $property = $reflection->getProperty('settings');
        $property->setAccessible(true);

        $options = $property->getValue($datatable);
        $options['method'] = Request::METHOD_OPTIONS;
        $property->setValue($datatable, $options);

        $datatable->handleRequest(Request::create('/foo'));
    }
}
