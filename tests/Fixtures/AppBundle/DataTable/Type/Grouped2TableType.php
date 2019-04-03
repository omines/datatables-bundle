<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Fixtures\AppBundle\DataTable\Type;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Tests\Fixtures\AppBundle\DataTable\Adapter\CustomORMAdapter;
use Tests\Fixtures\AppBundle\Entity\Company;

/**
 * GroupedTableType.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class Grouped2TableType implements DataTableTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(DataTable $dataTable, array $options)
    {
        $dataTable
            ->add('company', TextColumn::class, ['propertyPath' => '[0][name]'])
            ->add('employeeCount', TextColumn::class, ['propertyPath' => '[employeeCount]'])
            ->createAdapter(CustomORMAdapter::class, [
                'entity' => Company::class,
                'hydrate' => Query::HYDRATE_ARRAY,
                'query' => function (QueryBuilder $builder) {
                    $builder
                        ->select('c')
                        ->addSelect('count(e) AS employeeCount')
                        ->from(Company::class, 'c')
                        ->leftJoin('c.employees', 'e')
                        ->groupBy('c.id')
                    ;
                },
            ])
        ;
    }
}
