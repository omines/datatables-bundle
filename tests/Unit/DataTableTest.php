<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit;

use Omines\DatatablesBundle\Datatable;
use Omines\DatatablesBundle\DatatableFactory;
use PHPUnit\Framework\TestCase;

/**
 * DataTableTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class DataTableTest extends TestCase
{
    public function testFactory()
    {
        $factory = new DatatableFactory(['class' => 'foo'], ['dom' => 'bar']);

        $table = $factory->create(['name' => 'bar'], ['pageLength' => 684]);
        $this->assertSame('bar', $table->getSetting('name'));
        $this->assertSame('foo', $table->getSetting('class'));
        $this->assertSame('bar', $table->getOption('dom'));
        $this->assertSame(684, $table->getOption('pageLength'));

        $table = $factory->create(['class' => 'bar'], ['dom' => 'foo']);
        $this->assertSame('bar', $table->getSetting('class'));
        $this->assertSame('foo', $table->getOption('dom'));
        $this->assertNull($table->getSetting('none'));
        $this->assertNull($table->getOption('invalid'));
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function testInvalidSetting()
    {
        new Datatable(['setting' => 'foo'], []);
    }

    /**
     * @expectedException \Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException
     */
    public function testInvalidOption()
    {
        new Datatable([], ['option' => 'bar']);
    }
}
