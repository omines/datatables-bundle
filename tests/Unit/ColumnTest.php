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

use Omines\DataTablesBundle\Column\VirtualColumn;
use PHPUnit\Framework\TestCase;

/**
 * ColumnTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class ColumnTest extends TestCase
{
    public function testVirtualColumn()
    {
        $column = new VirtualColumn(['name' => 'foobar', 'index' => 1]);

        $this->assertFalse($column->isSearchable());
        $this->assertFalse($column->isOrderable());
    }
}
