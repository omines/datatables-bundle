<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Unit\Adapter;

use Omines\DataTablesBundle\Adapter\AdapterQuery;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableState;
use PHPUnit\Framework\TestCase;

class AdapterQueryTest extends TestCase
{
    public function testCountDeferredDefaultsToFalse(): void
    {
        $state = new DataTableState($this->createMock(DataTable::class));
        $query = new AdapterQuery($state);

        $this->assertFalse($query->isCountDeferred());
    }

    public function testCountDeferredCanBeSet(): void
    {
        $state = new DataTableState($this->createMock(DataTable::class));
        $query = new AdapterQuery($state);

        $result = $query->setCountDeferred(true);

        $this->assertTrue($query->isCountDeferred());
        $this->assertSame($query, $result);
    }
}
