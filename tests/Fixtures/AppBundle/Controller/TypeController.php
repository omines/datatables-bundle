<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Fixtures\AppBundle\Controller;

use Omines\DataTablesBundle\Controller\DataTablesTrait;
use Omines\DataTablesBundle\DataTable;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Tests\Fixtures\AppBundle\DataTable\Type\RegularPersonTableType;

/**
 * TypeController.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class TypeController extends Controller
{
    use DataTablesTrait;

    public function tableAction(Request $request)
    {
        $datatable = $this->createDataTableFromType(RegularPersonTableType::class)
            ->setName('persons')
            ->setMethod(Request::METHOD_GET)
            ->addOrderBy(1, DataTable::SORT_ASCENDING)
        ;

        return $datatable->handleRequest($request)->getResponse();
    }
}
