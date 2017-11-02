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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Omines\DataTablesBundle\Adapter\DoctrineORMAdapter;
use Omines\DataTablesBundle\Column\Column;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Tests\Fixtures\AppBundle\Entity\Person;

/**
 * ServicePersonTableType.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class ServicePersonTableType implements DataTableTypeInterface
{
    /** @var Registry */
    private $registry;

    /**
     * ServicePersonTableType constructor.
     *
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(DataTable $dataTable)
    {
        $dataTable
            ->column(Column::class, ['label' => 'id', 'field' => 'person.id'])
            ->column(Column::class, ['label' => 'firstName', 'name' => 'name', 'field' => 'person.firstName'])
            ->column(Column::class, ['label' => 'lastName', 'field' => 'person.lastName'])
            ->column(Column::class, ['label' => 'fullName', 'name' => 'fullName'])
            ->column(Column::class, ['label' => 'employer', 'name' => 'company', 'field' => 'company.name'])
            ->format(function ($row, Person $person) {
                $row['fullName'] = $person->getFirstName() . ' ' . $person->getLastName();

                return $row;
            })
            ->setAdapter(new DoctrineORMAdapter($this->registry, Person::class));
    }
}
