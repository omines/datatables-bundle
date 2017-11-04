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

use Omines\DataTablesBundle\Adapter\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Symfony\Component\Routing\RouterInterface;
use Tests\Fixtures\AppBundle\Entity\Person;

/**
 * ServicePersonTableType.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class ServicePersonTableType implements DataTableTypeInterface
{
    /** @var RouterInterface */
    private $router;

    /**
     * ServicePersonTableType constructor.
     *
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function configure(DataTable $dataTable)
    {
        $dataTable
            ->add('id', TextColumn::class)
            ->add('firstName', TextColumn::class, ['name' => 'name', 'field' => 'person.firstName'])
            ->add('lastName', TextColumn::class, ['field' => 'person.lastName'])
            ->add('fullName', TextColumn::class, ['name' => 'fullName'])
            ->add('company', TextColumn::class, ['label' => 'employer', 'name' => 'company', 'field' => 'company.name'])
            ->add('link', TextColumn::class, [
                'data' => function (Person $person) {
                    return sprintf('<a href="%s">%s, %s</a>', $this->router->generate('home'), $person->getLastName(), $person->getFirstName());
                },
            ])
            ->format(function ($row, Person $person) {
                $row['fullName'] = $person->getFirstName() . ' ' . $person->getLastName();

                return $row;
            })
            ->setAdapter(ORMAdapter::class, [
                'entity' => Person::class,
            ])
        ;
    }
}
