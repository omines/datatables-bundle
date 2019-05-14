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
use Omines\DataTablesBundle\DataTableFactory;
use Omines\DataTablesBundle\DataTableState;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Tests\Fixtures\AppBundle\DataTable\Type\GroupedTableType;

class ORMAdapterTest extends KernelTestCase
{
    /** @var DataTableFactory $factory */
    private $factory;

    protected function setUp()
    {
        $kernel = self::bootKernel();
        $this->factory = $kernel->getContainer()->get(DataTableFactory::class);
    }

    /**
     * @expectedException \Doctrine\ORM\Query\QueryException
     * @expectedExceptionMessage Iterate with fetch join in class Tests\Fixtures\AppBundle\Entity\Employee using association company not allowed.
     */
    public function testCountGroupedDataTable()
    {
        $datatable = $this->factory->createFromType(GroupedTableType::class);
        /** @var ORMAdapter $adapter */
        $adapter = $datatable->getAdapter();
        $data = $adapter->getData(new DataTableState($datatable));
    }
}
