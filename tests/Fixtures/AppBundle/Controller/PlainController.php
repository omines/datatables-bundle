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

use Doctrine\ORM\Query;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\TwigColumn;
use Omines\DataTablesBundle\Controller\DataTablesTrait;
use Omines\DataTablesBundle\DataTable;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Tests\Fixtures\AppBundle\Entity\Employee;

/**
 * PlainController.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class PlainController extends Controller
{
    use DataTablesTrait;

    public function tableAction(Request $request)
    {
        $datatable = $this->createDataTable()
            ->setName('persons')
            ->setMethod(Request::METHOD_GET)
            ->add('id', TextColumn::class, ['field' => 'employee.id'])
            ->add('firstName', TextColumn::class)
            ->add('lastName', TextColumn::class, ['field' => 'employee.lastName'])
            ->add('employedSince', DateTimeColumn::class, ['format' => 'd-m-Y'])
            ->add('fullName', TextColumn::class, [
                'data' => function (array $person) {
                    return "{$person['firstName']} <img src=\"https://symfony.com/images/v5/logos/sf-positive.svg\"> {$person['lastName']}";
                },
            ])
            ->add('employer', TextColumn::class, ['field' => 'company.name'])
            ->add('buttons', TwigColumn::class, [
                'template' => '@App/buttons.html.twig',
                'data' => '<button>Click me</button>',
            ])
            ->addOrderBy('lastName', DataTable::SORT_ASCENDING)
            ->addOrderBy('firstName', DataTable::SORT_DESCENDING)
            ->createAdapter(ORMAdapter::class, [
                'entity' => Employee::class,
                'hydrate' => Query::HYDRATE_ARRAY,
            ])
        ;

        return $datatable->handleRequest($request)->getResponse();
    }
}
