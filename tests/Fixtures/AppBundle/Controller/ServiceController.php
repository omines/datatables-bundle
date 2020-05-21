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

use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Tests\Fixtures\AppBundle\DataTable\Type\ServicePersonTableType;

/**
 * ServiceController.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class ServiceController extends AbstractController
{
    public function tableAction(Request $request, DataTableFactory $dataTableFactory)
    {
        $datatable = $dataTableFactory->createFromType(ServicePersonTableType::class, [], ['order' => [[1, 'asc']]])
            ->setName('persons')
            ->setMethod(Request::METHOD_GET)
        ;

        return $datatable->handleRequest($request)->getResponse();
    }
}
