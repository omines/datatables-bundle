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

use Omines\DataTablesBundle\Adapter\AbstractAdapter;
use Omines\DataTablesBundle\Column\Column;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableState;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Tests\Fixtures\AppBundle\Entity\Person;

/**
 * RegularPersonTableType.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class RegularPersonTableType implements DataTableTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(DataTable $dataTable)
    {
        $dataTable
            ->column(Column::class, ['label' => 'id', 'field' => 'person.id'])
            ->column(Column::class, ['label' => 'firstName', 'name' => 'name', 'field' => 'person.firstName'])
            ->column(Column::class, ['label' => 'lastName', 'field' => 'person.lastName'])
            ->setAdapter(new class() extends AbstractAdapter {
                public function handleState(DataTableState $state)
                {
                    return $this;
                }

                public function getData()
                {
                    return [
                        ['foo' => 'bar', 'bar' => 'foo'],
                    ];
                }
            });
    }
}
