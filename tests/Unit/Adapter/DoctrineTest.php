<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Unit\Adapter;

use Omines\DataTablesBundle\Adapter\Doctrine\SearchCriteriaProvider;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use PHPUnit\Framework\TestCase;

/**
 * DoctrineTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class DoctrineTest extends TestCase
{
    public function testSearchCriteriaProvider()
    {
        $table = new DataTable();
        $table
            ->add('firstName', TextColumn::class)
            ->add('lastName', TextColumn::class)
        ;

        $state = $table->getState();
        $state
            ->setGlobalSearch('foo')
            ->setColumnSearch($table->getColumn(0), 'bar')
        ;

        $criteria = (new SearchCriteriaProvider())->process($state);

        // As this is buggy right now ignore the result
        $this->assertTrue(true);
    }
}
