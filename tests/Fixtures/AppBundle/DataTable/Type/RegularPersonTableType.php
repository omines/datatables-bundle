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

use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableEvents;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Omines\DataTablesBundle\Event\DataTablePreResponseEvent;

/**
 * RegularPersonTableType.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class RegularPersonTableType implements DataTableTypeInterface
{
    public function configure(DataTable $dataTable, array $options): void
    {
        $dataTable
            ->add('firstName', TextColumn::class)
            ->add('lastName', TextColumn::class)
            ->add('lastActivity', DateTimeColumn::class, [
                'data' => function () {
                    return '2017-1-1 12:34:56';
                },
                'format' => 'd-m-Y',
            ])
            ->add('dummy', TextColumn::class, ['data' => fn () => ''])
            ->createAdapter(ArrayAdapter::class, [
                ['firstName' => 'Donald', 'lastName' => 'Trump'],
                ['firstName' => 'Barack', 'lastName' => 'Obama'],
                ['firstName' => 'George W.', 'lastName' => 'Bush'],
                ['firstName' => 'Bill', 'lastName' => 'Clinton'],
                ['firstName' => 'George H.W.', 'lastName' => 'Bush'],
                ['firstName' => 'Ronald', 'lastName' => 'Reagan'],
            ])
            ->setTransformer(function ($row) {
                $row['lastName'] = mb_strtoupper($row['lastName']);

                return $row;
            })
            ->addEventListener(DataTableEvents::PRE_RESPONSE, function (DataTablePreResponseEvent $event) {
                $table = $event->getTable();

                $table
                    ->add('email', TextColumn::class, [
                        'data' => fn ($context) => mb_strtolower($context['lastName']) . '@example.org',
                    ])
                    ->remove('dummy')
                ;
            })
        ;
    }
}
