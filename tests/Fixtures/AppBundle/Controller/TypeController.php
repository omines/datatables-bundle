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
    public function tableAction(Request $request)
    {
        /** @var DataTableFactory $factory */
        $factory = $this->get(DatatableFactory::class);
        $datatable = $factory->createFromType(RegularPersonTableType::class, ['name' => 'persons'], ['order' => [[1, 'asc']]]);

        return $datatable->handleRequest($request)->getResponse();
    }
}
