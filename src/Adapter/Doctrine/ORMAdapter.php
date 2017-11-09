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
use Omines\DataTablesBundle\Adapter\ArrayResultSet;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\AutomaticQueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\QueryBuilderProcessorInterface;
use Omines\DataTablesBundle\Adapter\ResultSetInterface;
use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\DataTableState;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

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

    /** @var array */
    private $aliases;

    /** @var array */
    private $fieldMap = [];

    /** @var array */
    private $propertyPathMap = [];

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
     * {@inheritdoc}
     */
    public function getData(DataTableState $state): ResultSetInterface
    {
        $builder = $this->createQueryBuilder($state);

        /** @var Query\Expr\From $fromClause */
        $fromClause = $builder->getDQLPart('from')[0];
        $identifier = "{$fromClause->getAlias()}.{$this->metadata->getSingleIdentifierFieldName()}";
        $totalRecords = $this->getCount($builder, $identifier);

        // Get record count after filtering
        $this->buildCriteria($builder, $state);
        $displayRecords = $this->getCount($builder, $identifier);

        /** @var Query\Expr\From $from */
        foreach ($builder->getDQLPart('from') as $from) {
            $this->aliases[$from->getAlias()] = [null, $this->manager->getMetadataFactory()->getMetadataFor($from->getFrom())];
        }

        // Alias all joins
        foreach ($builder->getDQLPart('join') as $joins) {
            /** @var Query\Expr\Join $join */
            foreach ($joins as $join) {
                list($origin, $target) = explode('.', $join->getJoin());

                $mapping = $this->aliases[$origin][1]->getAssociationMapping($target);
                $this->aliases[$join->getAlias()] = [$join->getJoin(), $this->manager->getMetadataFactory()->getMetadataFor($mapping['targetEntity'])];
            }
        }

        // Perform mapping of all referred fields and implied fields
        $identifierPropertyPath = $this->mapPropertyPath($identifier);
        foreach ($state->getDataTable()->getColumns() as $column) {
            $this->fieldMap[$column->getName()] = $field = $column->getField() ?: "{$fromClause->getAlias()}.{$column->getName()}";
            $this->propertyPathMap[$column->getName()] = $column->getPropertyPath() ?: $this->mapPropertyPath($field);
        }

        // Apply definitive view state for current 'page' of the table
        foreach ($state->getOrderBy() as list($column, $direction)) {
            /** @var AbstractColumn $column */
            $orderField = $column->getOrderField() ?: $this->fieldMap[$column->getName()] ?? null;
            if ($column->isOrderable() && !empty($orderField)) {
                $builder->addOrderBy($orderField, $direction);
            }
        }
        if ($state->getLength() > 0) {
            $builder
                ->setFirstResult($state->getStart())
                ->setMaxResults($state->getLength())
            ;
        }

        return new ArrayResultSet($this->getQueryData($builder->getQuery(), $state, $identifierPropertyPath), $totalRecords, $displayRecords);
    }

    /**
     * Performs an incrementally processing fetch on the provided query.
     *
     * @param Query $query
     * @param DataTableState $state
     * @param string|null $identifierPropertyPath supply this parameter to inject the identifier in the result rows
     * @return array
     */
    protected function getQueryData(Query $query, DataTableState $state, string $identifierPropertyPath = null): array
    {
        // TODO: Support query parameters
        $data = [];
        $accessor = PropertyAccess::createPropertyAccessor();
        $transformer = $state->getDataTable()->getTransformer();
        foreach ($query->iterate([], $this->hydrationMode) as $result) {
            $entity = $result[0];
            $row = [];
            if (null !== $identifierPropertyPath) {
                $row['DT_RowId'] = $accessor->getValue($entity, $identifierPropertyPath);
            }

            foreach ($state->getDataTable()->getColumns() as $column) {
                $propertyPath = $this->propertyPathMap[$column->getName()];
                $value = ($propertyPath && $accessor->isReadable($entity, $propertyPath)) ? $accessor->getValue($entity, $propertyPath) : null;
                $row[$column->getName()] = $column->transform($value, $entity);
            }
            if ($transformer) {
                $row = call_user_func($transformer, $row, $entity);
            }
            $data[] = $row;

            // Release memory by detaching from Doctrine
            $this->manager->detach($entity);
        }

        return $data;
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
     * @return string
     */
    private function mapPropertyPath($field)
    {
        $parts = explode('.', $field);
        if (count($parts) < 2) {
            throw new \RuntimeException(sprintf("Field name '%s' must consist at least of an alias and a field separated with a period", $field));
        }
        list($origin, $target) = $parts;

        $path = [$target];
        $current = $this->aliases[$origin][0];

        while (null !== $current) {
            list($origin, $target) = explode('.', $current);
            $path[] = $target;
            $current = $this->aliases[$origin][0];
        }

        if (Query::HYDRATE_ARRAY === $this->hydrationMode) {
            return '[' . implode('][', array_reverse($path)) . ']';
        } else {
            return implode('.', array_reverse($path));
        }
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @todo Make entity optional by extracting count/ID logic
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'entity' => null,
                'hydrate' => Query::HYDRATE_OBJECT,
                'query' => [],
            ])
            ->setAllowedTypes('entity', ['string']) //, 'null'])
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
