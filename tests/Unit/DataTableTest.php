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

use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableFactory;
use Omines\DataTablesBundle\DataTablesBundle;
use Omines\DataTablesBundle\DataTableState;
use PHPUnit\Framework\TestCase;
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
        $factory = new DataTableFactory(['class_name' => 'foo'], ['dom' => 'bar']);

        $table = $factory->create(['name' => 'bar'], ['pageLength' => 684]);
        $this->assertSame('bar', $table->getSetting('name'));
        $this->assertSame('foo', $table->getSetting('class_name'));
        $this->assertSame('bar', $table->getOption('dom'));
        $this->assertSame(684, $table->getOption('pageLength'));

        $table = $factory->create(['class_name' => 'bar'], ['dom' => 'foo']);
        $this->assertSame('bar', $table->getSetting('class_name'));
        $this->assertSame('foo', $table->getOption('dom'));
        $this->assertNull($table->getSetting('none'));
        $this->assertNull($table->getOption('invalid'));
    }

    public function testFactoryRemembersInstances()
    {
        $factory = new DataTableFactory([], []);
        $reflection = new \ReflectionClass(DataTableFactory::class);
        $property = $reflection->getProperty('resolvedTypes');
        $property->setAccessible(true);

        $this->assertEmpty($property->getValue($factory));
        $factory->createFromType(RegularPersonTableType::class);
        $factory->createFromType(RegularPersonTableType::class);
        $this->assertCount(1, $property->getValue($factory));
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage Could not resolve type
     */
    public function testFactoryFailsOnInvalidType()
    {
        (new DataTableFactory([], []))->createFromType('foobar');
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

    public function testDataTableState()
    {
        $state = new DataTableState(new DataTable());

        // Test sane defaults
        $this->assertSame(0, $state->getStart());
        $this->assertSame(-1, $state->getLength());
        $this->assertSame(0, $state->getDraw());
        $this->assertSame('', $state->getGlobalSearch());

        $state->setStart(5);
        $state->setLength(10);
        $state->setGlobalSearch('foo');

        $this->assertSame(5, $state->getStart());
        $this->assertSame(10, $state->getLength());
        $this->assertSame('foo', $state->getGlobalSearch());
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
}
