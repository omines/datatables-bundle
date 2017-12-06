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

use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use PHPUnit\Framework\TestCase;

/**
 * ColumnTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class ColumnTest extends TestCase
{
    public function testTextColumn()
    {
        $column = new TextColumn('test', 1, [
            'data' => 'bar',
            'render' => 'foo%s',
        ]);

        $this->assertFalse($column->isRaw());
        $this->assertSame('foobar', $column->transform(null));

        $column->setDataTable((new DataTable())->setName('foo'));
        $this->assertSame('foo', $column->getDataTable()->getName());
    }

    public function testColumnWithClosures()
    {
        $column = new TextColumn('test', 1, [
            'data' => function ($context, $value) {
                return 'bar';
            },
            'render' => function ($value) {
                return mb_strtoupper($value);
            },
        ]);

        $this->assertFalse($column->isRaw());
        $this->assertSame('BAR', $column->transform(null));
    }
}
