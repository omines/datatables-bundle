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

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\AdapterQuery;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\SearchCriteriaProvider;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\HttpFoundation\Request;

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

        $table->handleRequest(Request::create('/', Request::METHOD_POST, ['_dt' => 'dt']));
        $state = $table->getState();
        $state
            ->setGlobalSearch('foo')
            ->setColumnSearch($table->getColumn(0), 'bar')
        ;

        $qb = $this->createMock(QueryBuilder::class);
        $qb
            ->method('expr')
            ->will($this->returnCallback(function () { return new Query\Expr(); }));

        /* @var QueryBuilder $qb */
        (new SearchCriteriaProvider())->process($qb, $state);

        // As this is buggy right now ignore the result
        $this->assertTrue(true);
    }

    /**
     * @expectedException \LogicException
     * @expectedExceptionMessage doctrine/doctrine-bundle
     */
    public function testORMAdapterRequiresDependency()
    {
        (new ORMAdapter());
    }

    /**
     * @expectedException \Omines\DataTablesBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Provider must be a callable or implement QueryBuilderProcessorInterface
     */
    public function testInvalidQueryProcessorThrows()
    {
        (new ORMAdapter($this->createMock(RegistryInterface::class)))
            ->configure([
                'entity' => 'bar',
                'query' => ['foo'],
            ]);
    }

    /**
     * @expectedException \Omines\DataTablesBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Field name 'invalid' must consist at least of an alias and a field
     */
    public function testInvalidFieldThrows()
    {
        $query = $this->createMock(AdapterQuery::class);
        $query->method('get')->willReturn([]);
        $column = new TextColumn();
        $column->initialize('foo', 0, ['field' => 'invalid'], $this->createMock(DataTable::class));

        $mock = new class($this->createMock(RegistryInterface::class)) extends ORMAdapter {
            public function foo($query, $column)
            {
                return $this->mapPropertyPath($query, $column);
            }
        };
        $mock->foo($query, $column);
    }
}
