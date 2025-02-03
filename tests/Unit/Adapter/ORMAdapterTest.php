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

use Doctrine\ORM\Query;
use Doctrine\ORM\Query\QueryException;
use Omines\DataTablesBundle\Adapter\Doctrine\Event\ORMAdapterQueryEvent;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\DataTableFactory;
use Omines\DataTablesBundle\DataTableState;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Tests\Fixtures\AppBundle\DataTable\Type\GroupedTableType;
use Tests\Fixtures\AppBundle\DataTable\Type\ServicePersonTableType;

class ORMAdapterTest extends KernelTestCase
{
    /** @var DataTableFactory $factory */
    private $factory;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->factory = $kernel->getContainer()->get(DataTableFactory::class);
    }

    public function testCountGroupedDataTable(): void
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Iterate with fetch join in class Tests\Fixtures\AppBundle\Entity\Employee using association company not allowed.');

        $datatable = $this->factory->createFromType(GroupedTableType::class);
        /** @var ORMAdapter $adapter */
        $adapter = $datatable->getAdapter();
        $data = $adapter->getData(new DataTableState($datatable));
        iterator_to_array($data->getData());  // Evaluate the iterator to trigger the exception
    }

    /**
     * Tests that column searches are applied when `field` is set by ORMAdapter.
     *
     * When `field` and `searchable` are not set manually, isSearchable() will only
     * return true after the `field` option is set by ORMAdapter. This tests that the
     * column search still is applied correctly.
     */
    public function testColumnSearch(): void
    {
        $datatable = $this->factory->createFromType(ServicePersonTableType::class)
            ->setMethod(Request::METHOD_GET);
        $datatable->handleRequest(Request::create('/?_dt=' . $datatable->getName() . '&columns[1][search][value]=John'));

        // At this point, $searchColumns is empty because isSearchable() returns false due
        // to `field` not being set yet. Ideally it should contain the search value.
        $searchColumns = $datatable->getState()->getSearchColumns();
        $this->assertCount(0, $searchColumns);

        // After calling getData(), the column search should be correctly returned.
        $datatable->getAdapter()->getData($datatable->getState());

        $searchColumns = $datatable->getState()->getSearchColumns();
        $this->assertCount(1, $searchColumns);
        $this->assertSame('John', $searchColumns['firstName']['search']);
    }

    public function testORMAdapterQueryEvent(): void
    {
        $query = $this->createMock(Query::class);
        $event = new ORMAdapterQueryEvent($query);
        $this->assertSame($query, $event->getQuery());
    }
}
