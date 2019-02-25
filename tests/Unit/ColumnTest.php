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

use Omines\DataTablesBundle\Column\BoolColumn;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\MapColumn;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\DataTable;
use PHPUnit\Framework\TestCase;

/**
 * ColumnTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class ColumnTest extends TestCase
{
    public function testDateTimeColumn()
    {
        $column = new DateTimeColumn();
        $column->initialize('test', 1, [
            'nullValue' => 'foo',
            'format' => 'd-m-Y',
        ], (new DataTable())->setName('foo'));

        $this->assertSame('03-04-2015', $column->transform('2015-04-03'));
        $this->assertSame('foo', $column->transform(null));
    }

    public function testTextColumn()
    {
        $column = new TextColumn();
        $column->initialize('test', 1, [
            'data' => 'bar',
            'render' => 'foo%s',
        ], (new DataTable())->setName('foo'));

        $this->assertFalse($column->isRaw());
        $this->assertSame('foobar', $column->transform(null));
        $this->assertSame('foo', $column->getDataTable()->getName());
    }

    public function testBoolColumn()
    {
        $column = new BoolColumn();
        $column->initialize('test', 1, [
             'trueValue' => 'yes',
             'nullValue' => '<em>null</em>',
        ], new DataTable());

        $this->assertSame('yes', $column->transform(5));
        $this->assertSame('yes', $column->transform(true));
        $this->assertSame('false', $column->transform(false));
        $this->assertStringStartsWith('<em>', $column->transform());

        $this->assertTrue($column->isValidForSearch('yes'));
        $this->assertFalse($column->isValidForSearch('true'));

        $this->assertTrue($column->getRightExpr('yes'));
        $this->assertFalse($column->getRightExpr('true'));
    }

    public function testMapColumn()
    {
        $column = new MapColumn();
        $column->initialize('test', 1, [
            'default' => 'foo',
            'map' => [
                1 => 'bar',
                2 => 'baz',
            ],
        ], new DataTable());

        $this->assertSame('foo', $column->transform(0));
        $this->assertSame('bar', $column->transform(1));
        $this->assertSame('baz', $column->transform(2));
        $this->assertSame('foo', $column->transform(3));
    }

    public function testNumberColumn()
    {
        $column = new NumberColumn();
        $column->initialize('test', 1, [], new DataTable());

        $this->assertSame('5', $column->transform(5));
        $this->assertSame('1', $column->transform(true));
        $this->assertSame('684', $column->transform('684'));

        $this->assertFalse($column->isRaw());
        $this->assertTrue($column->isValidForSearch(684));
        $this->assertFalse($column->isValidForSearch('foo.bar'));
    }

    public function testColumnWithClosures()
    {
        $column = new TextColumn();
        $column->initialize('test', 1, [
            'data' => function ($context, $value) {
                return 'bar';
            },
            'render' => function ($value) {
                return mb_strtoupper($value);
            },
        ], new DataTable());

        $this->assertFalse($column->isRaw());
        $this->assertSame('BAR', $column->transform(null));
    }

    /**
     * @expectedException \Omines\DataTablesBundle\Exception\MissingDependencyException
     * @expectedExceptionMessage You must have TwigBundle installed to use
     */
    public function testTwigDependencyDetection()
    {
        new TwigColumn();
    }
}
