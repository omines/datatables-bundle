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

use Omines\DataTablesBundle\Adapter\DoctrineORMAdapter;
use Omines\DataTablesBundle\Column\Column;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Tests\Fixtures\AppBundle\Entity\Person;

/**
 * PlainController.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class PlainController extends Controller
{
    public function tableAction(Request $request)
    {
        /** @var DataTable $datatable */
        $datatable = $this->get(DatatableFactory::class)->create(['name' => 'persons'], ['order' => [[1, 'asc']]])
            ->column(Column::class, ['label' => 'id', 'field' => 'person.id'])
            ->column(Column::class, ['label' => 'firstName', 'name' => 'name', 'field' => 'person.firstName'])
            ->column(Column::class, ['label' => 'lastName', 'field' => 'person.lastName'])
            ->format(function ($row, Person $person) {
                $row['fullName'] = $person->getFirstName() . ' ' . $person->getLastName();

                return $row;
            })
            ->setAdapter(new DoctrineORMAdapter($this->getDoctrine(), Person::class));

        return $datatable->handleRequest($request)->getResponse();
    }
}
