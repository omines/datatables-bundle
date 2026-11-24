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

use Omines\DataTablesBundle\Adapter\ResultSet;
use PHPUnit\Framework\TestCase;

class ResultSetTest extends TestCase
{
    public function testCountDeferredDefaultsToFalse(): void
    {
        $resultSet = new ResultSet(new \EmptyIterator(), 10, 5);

        $this->assertFalse($resultSet->isCountDeferred());
        $this->assertSame(10, $resultSet->getTotalRecords());
        $this->assertSame(5, $resultSet->getTotalDisplayRecords());
    }

    public function testCountDeferredCanBeEnabled(): void
    {
        $resultSet = new ResultSet(new \EmptyIterator(), 0, 0, true);

        $this->assertTrue($resultSet->isCountDeferred());
        $this->assertSame(0, $resultSet->getTotalRecords());
        $this->assertSame(0, $resultSet->getTotalDisplayRecords());
    }
}
