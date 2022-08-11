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
use Omines\DataTablesBundle\Column\MoneyColumn;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\Column\TwigStringColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\Exception\MissingDependencyException;
use Omines\DataTablesBundle\Exporter\DataTableExporterManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Twig\Environment as Twig;

/**
 * ColumnTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class ColumnTest extends TestCase
{
    public function testDateTimeColumn(): void
    {
        $column = new DateTimeColumn();
        $column->initialize('test', 1, [
            'nullValue' => 'foo',
            'format' => 'd-m-Y',
        ], $this->createDataTable()->setName('foo'));

        $this->assertSame('03-04-2015', $column->transform('2015-04-03'));
        $this->assertSame('foo', $column->transform(null));
    }

    public function testDateTimeColumnWithCreateFromFormat(): void
    {
        $column = new DateTimeColumn();
        $column->initialize('test', 1, [
            'format' => 'd.m.Y H:i:s',
            'createFromFormat' => 'Y-m-d\TH:i:sP',
        ], $this->createDataTable()->setName('foo'));

        $this->assertSame('19.02.2020 22:30:34', $column->transform('2020-02-19T22:30:34+00:00'));
    }

    public function testTextColumn(): void
    {
        $column = new TextColumn();
        $column->initialize('test', 1, [
            'data' => 'bar',
            'render' => 'foo%s',
        ], $this->createDataTable()->setName('foo'));

        $this->assertFalse($column->isRaw());
        $this->assertSame('foobar', $column->transform(null));
        $this->assertSame('foo', $column->getDataTable()->getName());
    }

    public function testBoolColumn(): void
    {
        $column = new BoolColumn();
        $column->initialize('test', 1, [
            'trueValue' => 'yes',
            'nullValue' => '<em>null</em>',
        ], $this->createDataTable());

        $this->assertSame('yes', $column->transform(5));
        $this->assertSame('yes', $column->transform(true));
        $this->assertSame('false', $column->transform(false));
        $this->assertStringStartsWith('<em>', $column->transform());

        $this->assertTrue($column->isValidForSearch('yes'));
        $this->assertFalse($column->isValidForSearch('true'));

        $this->assertTrue($column->getRightExpr('yes'));
        $this->assertFalse($column->getRightExpr('true'));
    }

    public function testMapColumn(): void
    {
        $column = new MapColumn();
        $column->initialize('test', 1, [
            'default' => 'foo',
            'map' => [
                1 => 'bar',
                2 => 'baz',
            ],
        ], $this->createDataTable());

        $this->assertSame('foo', $column->transform(0));
        $this->assertSame('bar', $column->transform(1));
        $this->assertSame('baz', $column->transform(2));
        $this->assertSame('foo', $column->transform(3));
    }

    public function testNumberColumn(): void
    {
        $column = new NumberColumn();
        $column->initialize('test', 1, [], $this->createDataTable());

        $this->assertSame('5', $column->transform(5));
        $this->assertSame('1', $column->transform(true));
        $this->assertSame('684', $column->transform('684'));

        $this->assertFalse($column->isRaw());
        $this->assertTrue($column->isValidForSearch(684));
        $this->assertFalse($column->isValidForSearch('foo.bar'));
    }

    public function testColumnWithClosures(): void
    {
        $column = new TextColumn();
        $column->initialize('test', 1, [
            'data' => function ($context, $value) {
                return 'bar';
            },
            'render' => function ($value) {
                return mb_strtoupper($value);
            },
        ], $this->createDataTable());

        $this->assertFalse($column->isRaw());
        $this->assertSame('BAR', $column->transform(null));
    }

    public function testMoneyColumn(): void
    {
        $column = new MoneyColumn();
        //Test with defaults
        $column->initialize('test', 1, [], $this->createDataTable());
        $this->assertSame('5.00', $column->transform(5));
        $this->assertSame('1.00', $column->transform(true));
        $this->assertSame('340.00', $column->transform('340'));
        $this->assertSame('1,340.00', $column->transform('1340'));
        $this->assertFalse($column->isRaw());
        $this->assertTrue($column->isValidForSearch(684));
        $this->assertFalse($column->isValidForSearch('foo.bar'));

        // test with money options
        $column->initialize('test', 1, ['divisor' => 100, 'currency' => 'GBP'], new DataTable($this->createMock(EventDispatcher::class)));
        $this->assertSame('£5.00', $column->transform(500));
        $this->assertSame('£0.01', $column->transform(true));
        $this->assertSame('£3.40', $column->transform('340'));
        $this->assertSame('£1,340.00', $column->transform('134000'));
        $this->assertEquals('GBP', $column->getCurrency());
        $this->assertEquals(100, $column->getDivisor());
        $this->assertEquals(2, $column->getScale());

        // test DE locale
        \Locale::setDefault('de');
        $column->initialize('test', 1, ['divisor' => 100, 'currency' => 'EUR'], new DataTable($this->createMock(EventDispatcher::class)));
        $this->assertSame('5,00€', $column->transform(500));
        $this->assertSame('1.340,00€', $column->transform('134000'));

        // differnt scale
        $column->initialize('test', 1, ['scale' => 4, 'divisor' => 100, 'currency' => 'EUR'], new DataTable($this->createMock(EventDispatcher::class)));
        $this->assertSame('5,0000€', $column->transform(500));
        $this->assertSame('1.340,2800€', $column->transform('134028'));
        $this->assertEquals(4, $column->getScale());

        //raw input
        $column->initialize('test', 1, ['raw' => true], new DataTable($this->createMock(EventDispatcher::class)));
        $this->assertSame('500', $column->transform(500));
        $this->assertSame('134028', $column->transform('134028'));
        $this->assertTrue($column->isRaw());
    }

    /**
     * @expectedException \Omines\DataTablesBundle\Exception\MissingDependencyException
     * @expectedExceptionMessage You must have TwigBundle installed to use
     */
    public function testTwigDependencyDetection(): void
    {
        $this->expectException(MissingDependencyException::class);
        $this->expectExceptionMessage('You must have TwigBundle installed to use');

        new TwigColumn();
    }

    public function testTwigStringColumnExtensionDetection(): void
    {
        $this->expectException(MissingDependencyException::class);
        $this->expectExceptionMessage('You must have StringLoaderExtension enabled to use');

        new TwigStringColumn($this->createMock(Twig::class));
    }

    private function createDataTable(): DataTable
    {
        return new DataTable($this->createMock(EventDispatcher::class), $this->createMock(DataTableExporterManager::class));
    }
}
