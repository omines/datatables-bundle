<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Adapter\Doctrine;

use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Omines\DataTablesBundle\Adapter\AdapterQuery;
use Omines\DataTablesBundle\Adapter\Doctrine\Event\ORMAdapterQueryEvent;
use Omines\DataTablesBundle\Column\AbstractColumn;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Similar to ORMAdapter this class allows to access objects from the doctrine ORM.
 * Unlike the default ORMAdapter supports Fetch Joins (additional entites are fetched from DB via joins) using
 * the Doctrine Paginator.
 *
 * @author Jan Böhmer
 */
class FetchJoinORMAdapter extends ORMAdapter
{
    protected $use_simple_total;

    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        //Enforce object hydration mode (fetch join only works for objects)
        $resolver->addAllowedValues('hydrate', Query::HYDRATE_OBJECT);

        /*
         * Add the possibility to replace the query for total entity count through a very simple one, to improve performance.
         * You can only use this option, if you did not apply any criteria to your total count.
         */
        $resolver->setDefault('simple_total_query', false);

        return $resolver;
    }

    protected function afterConfiguration(array $options): void
    {
        parent::afterConfiguration($options);

        $this->use_simple_total = $options['simple_total_query'];
    }

    protected function prepareQuery(AdapterQuery $query)
    {
        $state = $query->getState();
        $query->set('qb', $builder = $this->createQueryBuilder($state));
        $query->set('rootAlias', $rootAlias = $builder->getDQLPart('from')[0]->getAlias());

        // Provide default field mappings if needed
        foreach ($state->getDataTable()->getColumns() as $column) {
            if (null === $column->getField() && isset($this->metadata->fieldMappings[$name = $column->getName()])) {
                $column->setOption('field', "{$rootAlias}.{$name}");
            }
        }

        /** @var Query\Expr\From $fromClause */
        $fromClause = $builder->getDQLPart('from')[0];
        $identifier = "{$fromClause->getAlias()}.{$this->metadata->getSingleIdentifierFieldName()}";

        //Use simpler (faster) total count query if the user wanted so...
        if ($this->use_simple_total) {
            $query->setTotalRows($this->getSimpleTotalCount($builder));
        } else {
            $query->setTotalRows($this->getCount($builder, $identifier));
        }

        // Get record count after filtering
        $this->buildCriteria($builder, $state);
        $query->setFilteredRows($this->getCount($builder, $identifier));

        // Perform mapping of all referred fields and implied fields
        $aliases = $this->getAliases($query);
        $query->set('aliases', $aliases);
        $query->setIdentifierPropertyPath($this->mapFieldToPropertyPath($identifier, $aliases));
    }

    public function getResults(AdapterQuery $query): \Traversable
    {
        $builder = $query->get('qb');
        $state = $query->getState();

        // Apply definitive view state for current 'page' of the table
        foreach ($state->getOrderBy() as list($column, $direction)) {
            /** @var AbstractColumn $column */
            if ($column->isOrderable()) {
                $builder->addOrderBy($column->getOrderField(), $direction);
            }
        }
        if ($state->getLength() > 0) {
            $builder
                ->setFirstResult($state->getStart())
                ->setMaxResults($state->getLength());
        }

        $query = $builder->getQuery();
        $event = new ORMAdapterQueryEvent($query);
        $state->getDataTable()->getEventDispatcher()->dispatch($event, ORMAdapterEvents::PRE_QUERY);

        //Use Doctrine paginator for result iteration
        $paginator = new Paginator($query);

        foreach ($paginator->getIterator() as $result) {
            yield $result;
            $this->manager->detach($result);
        }
    }

    public function getCount(QueryBuilder $queryBuilder, $identifier)
    {
        $paginator = new Paginator($queryBuilder);

        return $paginator->count();
    }

    protected function getSimpleTotalCount(QueryBuilder $queryBuilder)
    {
        /** The paginator count queries can be rather slow, so when query for total count (100ms or longer),
         * just return the entity count.
         */
        /** @var Query\Expr\From $from_expr */
        $from_expr = $queryBuilder->getDQLPart('from')[0];

        return $this->manager->getRepository($from_expr->getFrom())->count([]);
    }
}
