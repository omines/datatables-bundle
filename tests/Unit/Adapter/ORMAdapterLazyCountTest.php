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

use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Omines\DataTablesBundle\DataTableState;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Tests\Fixtures\AppBundle\Entity\Employee;

class ORMAdapterLazyCountTest extends KernelTestCase
{
    private DataTableFactory $factory;

    protected function setUp(): void
    {
        $kernel = self::bootKernel();
        $this->factory = $kernel->getContainer()->get(DataTableFactory::class);
    }

    public function testLazyTotalCountReturnsDeferredResultSet(): void
    {
        $datatable = $this->factory->create()
            ->add('firstName', TextColumn::class)
            ->add('lastName', TextColumn::class)
            ->createAdapter(ORMAdapter::class, [
                'entity' => Employee::class,
                'lazy_total_count' => true,
            ]);

        $state = new DataTableState($datatable);
        $resultSet = $datatable->getAdapter()->getData($state);

        $this->assertTrue($resultSet->isCountDeferred());
        $this->assertSame(0, $resultSet->getTotalRecords());
        $this->assertGreaterThan(0, $resultSet->getTotalDisplayRecords());
    }

    public function testLazyFilteredCountReturnsDeferredResultSet(): void
    {
        $datatable = $this->factory->create()
            ->add('firstName', TextColumn::class)
            ->add('lastName', TextColumn::class)
            ->createAdapter(ORMAdapter::class, [
                'entity' => Employee::class,
                'lazy_filtered_count' => true,
            ]);

        $state = new DataTableState($datatable);
        $resultSet = $datatable->getAdapter()->getData($state);

        $this->assertTrue($resultSet->isCountDeferred());
        $this->assertGreaterThan(0, $resultSet->getTotalRecords());
        $this->assertSame(0, $resultSet->getTotalDisplayRecords());
    }

    public function testBothLazyCountsReturnsDeferredResultSet(): void
    {
        $datatable = $this->factory->create()
            ->add('firstName', TextColumn::class)
            ->add('lastName', TextColumn::class)
            ->createAdapter(ORMAdapter::class, [
                'entity' => Employee::class,
                'lazy_total_count' => true,
                'lazy_filtered_count' => true,
            ]);

        $state = new DataTableState($datatable);
        $resultSet = $datatable->getAdapter()->getData($state);

        $this->assertTrue($resultSet->isCountDeferred());
        $this->assertSame(0, $resultSet->getTotalRecords());
        $this->assertSame(0, $resultSet->getTotalDisplayRecords());
    }

    public function testWithoutLazyCountsReturnsNonDeferredResultSet(): void
    {
        $datatable = $this->factory->create()
            ->add('firstName', TextColumn::class)
            ->add('lastName', TextColumn::class)
            ->createAdapter(ORMAdapter::class, [
                'entity' => Employee::class,
            ]);

        $state = new DataTableState($datatable);
        $resultSet = $datatable->getAdapter()->getData($state);

        $this->assertFalse($resultSet->isCountDeferred());
        $this->assertGreaterThan(0, $resultSet->getTotalRecords());
        $this->assertGreaterThan(0, $resultSet->getTotalDisplayRecords());
    }

    public function testCountRequestComputesActualCounts(): void
    {
        $datatable = $this->factory->create()
            ->add('firstName', TextColumn::class)
            ->add('lastName', TextColumn::class)
            ->createAdapter(ORMAdapter::class, [
                'entity' => Employee::class,
                'lazy_total_count' => true,
                'lazy_filtered_count' => true,
            ]);

        // Simulate a count request
        $datatable->handleRequest(Request::create('/foo', Request::METHOD_POST, [
            '_dt' => $datatable->getName(),
            '_dt_count' => 1,
            'draw' => 1,
        ]));

        $this->assertTrue($datatable->isCountRequest());

        $resultSet = $datatable->getAdapter()->getData($datatable->getState());

        // On a count request, actual counts should be computed even with lazy options
        $this->assertFalse($resultSet->isCountDeferred());
        $this->assertGreaterThan(0, $resultSet->getTotalRecords());
        $this->assertGreaterThan(0, $resultSet->getTotalDisplayRecords());
    }
}
