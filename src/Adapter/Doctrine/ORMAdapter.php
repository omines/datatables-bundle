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

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\AdapterQuery;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\AutomaticQueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\QueryBuilderProcessorInterface;
use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\DataTableState;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ORMAdapter.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 * @author Robbert Beesems <robbert.beesems@omines.com>
 */
class ORMAdapter extends DoctrineAdapter
{
    /** @var EntityManager */
    private $manager;

    /** @var \Doctrine\ORM\Mapping\ClassMetadata */
    private $metadata;

    /** @var int */
    private $hydrationMode;

    /** @var QueryBuilderProcessorInterface[] */
    private $queryBuilderProcessors;

    /**
     * {@inheritdoc}
     */
    protected function handleOptions(array $options)
    {
        parent::handleOptions($options);

        // Enable automated mode or just get the general default entity manager
        if (null === ($this->manager = $this->registry->getManagerForClass($options['entity']))) {
            throw new \LogicException(sprintf('There is no manager for entity "%s"', $options['entity']));
        }
        $this->metadata = $this->manager->getClassMetadata($options['entity']);
        if (empty($options['query'])) {
            $options['query'] = [new AutomaticQueryBuilder($this->manager, $this->metadata)];
        }

        // Set options
        $this->hydrationMode = $options['hydrate'];
        $this->queryBuilderProcessors = $options['query'];
    }

    /**
     * @param AdapterQuery $query
     */
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
        $query->setTotalRows($this->getCount($builder, $identifier));

        // Get record count after filtering
        $this->buildCriteria($builder, $state);
        $query->setFilteredRows($this->getCount($builder, $identifier));

        /** @var Query\Expr\From $from */
        $aliases = [];
        foreach ($builder->getDQLPart('from') as $from) {
            $aliases[$from->getAlias()] = [null, $this->manager->getMetadataFactory()->getMetadataFor($from->getFrom())];
        }

        // Alias all joins
        foreach ($builder->getDQLPart('join') as $joins) {
            /** @var Query\Expr\Join $join */
            foreach ($joins as $join) {
                list($origin, $target) = explode('.', $join->getJoin());

                $mapping = $aliases[$origin][1]->getAssociationMapping($target);
                $aliases[$join->getAlias()] = [$join->getJoin(), $this->manager->getMetadataFactory()->getMetadataFor($mapping['targetEntity'])];
            }
        }

        // Perform mapping of all referred fields and implied fields
        $query->set('aliases', $aliases);
        $query->setIdentifierPropertyPath($this->mapFieldToPropertyPath($identifier, $aliases));
    }

    /**
     * {@inheritdoc}
     */
    protected function mapPropertyPath(AdapterQuery $query, AbstractColumn $column)
    {
        return $this->mapFieldToPropertyPath($column->getField(), $query->get('aliases'));
    }

    /**
     * @param AdapterQuery $query
     * @return \Traversable
     */
    protected function getResults(AdapterQuery $query): \Traversable
    {
        /** @var QueryBuilder $builder */
        $builder = $query->get('qb');
        $state = $query->getState();

        // Apply definitive view state for current 'page' of the table
        foreach ($state->getOrderBy() as list($column, $direction)) {
            /** @var AbstractColumn $column */
            if ($column->isOrderable()) {
                $orderField = $column->getOrderField() ?: $column->getField();
                $builder->addOrderBy($orderField, $direction);
            }
        }
        if ($state->getLength() > 0) {
            $builder
                ->setFirstResult($state->getStart())
                ->setMaxResults($state->getLength())
            ;
        }

        foreach ($builder->getQuery()->iterate([], $this->hydrationMode) as $result) {
            yield $entity = $result[0];
            $this->manager->detach($entity);
        }
    }

    /**
     * @param DataTableState $state
     */
    protected function buildCriteria(QueryBuilder $queryBuilder, DataTableState $state)
    {
        foreach ($this->criteriaProcessors as $processor) {
            if ($criteria = $processor->process($state)) {
                $queryBuilder->addCriteria($criteria);
            }
        }
    }

    /**
     * @param DataTableState $state
     * @return QueryBuilder
     */
    protected function createQueryBuilder(DataTableState $state): QueryBuilder
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->manager->createQueryBuilder();

        // Run all query builder processors in order
        foreach ($this->queryBuilderProcessors as $processor) {
            $processor->process($queryBuilder, $state);
        }

        return $queryBuilder;
    }

    /**
     * @param $identifier
     * @return int
     */
    protected function getCount(QueryBuilder $queryBuilder, $identifier)
    {
        $qb = clone $queryBuilder;

        $qb->resetDQLPart('orderBy');
        $gb = $qb->getDQLPart('groupBy');
        if (empty($gb) || !in_array($identifier, $gb, true)) {
            $qb->select($qb->expr()->count($identifier));

            return (int) $qb->getQuery()->getSingleScalarResult();
        } else {
            $qb->resetDQLPart('groupBy');
            $qb->select($qb->expr()->countDistinct($identifier));

            return (int) $qb->getQuery()->getSingleScalarResult();
        }
    }

    /**
     * @param string $field
     * @param array $aliases
     * @return string
     */
    private function mapFieldToPropertyPath($field, array $aliases = [])
    {
        $parts = explode('.', $field);
        if (count($parts) < 2) {
            throw new \RuntimeException(sprintf("Field name '%s' must consist at least of an alias and a field separated with a period", $field));
        }
        list($origin, $target) = $parts;

        $path = [$target];
        $current = $aliases[$origin][0];

        while (null !== $current) {
            list($origin, $target) = explode('.', $current);
            $path[] = $target;
            $current = $aliases[$origin][0];
        }

        if (Query::HYDRATE_ARRAY === $this->hydrationMode) {
            return '[' . implode('][', array_reverse($path)) . ']';
        } else {
            return implode('.', array_reverse($path));
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'hydrate' => Query::HYDRATE_OBJECT,
                'query' => [],
            ])
            ->setRequired('entity')
            ->setAllowedTypes('entity', ['string'])
            ->setAllowedTypes('hydrate', 'int')
            ->setAllowedTypes('query', [QueryBuilderProcessorInterface::class, 'array', 'callable'])
            ->setNormalizer('query', function (Options $options, $value) {
                if (is_callable($value)) {
                    return [new class($value) implements QueryBuilderProcessorInterface {
                        private $callable;

                        public function __construct(callable $value)
                        {
                            $this->callable = $value;
                        }

                        public function process(QueryBuilder $queryBuilder, DataTableState $state)
                        {
                            return call_user_func($this->callable, $queryBuilder, $state);
                        }
                    }];
                } elseif (is_array($value)) {
                    return $value;
                }

                return [$value];
            })
        ;
    }
}
