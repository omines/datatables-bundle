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
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Tests\Fixtures\AppBundle\DataTable\Type\GroupedTableType;

/**
 * GroupedController.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class GroupedController extends Controller
{
    use DataTablesTrait;

    public function tableAction(Request $request)
    {
        $datatable = $this->createDataTableFromType(GroupedTableType::class)
            ->setName('companies')
            ->setMethod(Request::METHOD_GET)
        ;

        return $datatable->handleRequest($request)->getResponse();
    }
}
