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

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Tests\Fixtures\AppBundle\Entity\Employee;
use Tests\Fixtures\AppBundle\Entity\Person;

/**
 * CustomQueryTableType.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class CustomQueryTableType implements DataTableTypeInterface
{
    /**
     * {@inheritdoc}
     */
    public function configure(DataTable $dataTable, array $options)
    {
        $dataTable
            ->add('firstName', TextColumn::class)
            ->add('lastName', TextColumn::class)
            ->add('fullName', TextColumn::class)
            ->add('company', TextColumn::class, ['field' => 'c.name'])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Person::class,
                'query' => function (QueryBuilder $builder) {
                    $builder
                        ->select('e')
                        ->addSelect('c')
                        ->from(Employee::class, 'e')
                        ->leftJoin('e.company', 'c')
                    ;
                },
                'criteria' => function (QueryBuilder $builder) {
                    $builder->andWhere($builder->expr()->like('c.name', ':test'))->setParameter('test', '%ny 2%');
                //$builder->addCriteria(Criteria::create()->andWhere(new Comparison('c.name', Comparison::CONTAINS, 'ny 2')));
                },
            ])
        ;

        /** @var ORMAdapter $adapter */
        $adapter = $dataTable->getAdapter();
        $adapter->addCriteriaProcessor(function () { return Criteria::create()->where(new Comparison('firstName', Comparison::CONTAINS, '3')); });
    }
}
