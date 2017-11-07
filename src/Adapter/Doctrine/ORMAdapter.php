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

    /**
     * {@inheritdoc}
     */
    protected function handleOptions(array $options)
    {
        parent::handleOptions($options);

        // Enable automated mode or just get the general default entity manager
        if (isset($options['entity'])) {
            if (null === ($this->manager = $this->registry->getManagerForClass($options['entity']))) {
                throw new \LogicException(sprintf('There is no manager for entity "%s"', $options['entity']));
            }
            $this->metadata = $this->manager->getClassMetadata($options['entity']);
            if (empty($options['query'])) {
                $options['query'] = [new AutomaticQueryBuilder($this->manager, $this->metadata)];
            }
        } else {
            if (empty($options['query'])) {
                throw new \LogicException("You must either provide the 'entity' property or at least one query processor");
            }
            $this->manager = $this->registry->getManager();
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

        // Apply definitive view state for current 'page' of the table
        $this->buildOrder($builder, $state->getColumns());
        if ($state->getLength() > 0) {
            $builder->setFirstResult($state->getStart())->setMaxResults($state->getLength());
        }

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

        $identifierPropertyPath = $this->mapPropertyPath($identifier);
        foreach ($state->getColumns() as $column) {
            if (null === $column->getPropertyPath()) {
                if (null === ($field = $column->getField())) {
                    $field = "{$fromClause->getAlias()}.{$column->getName()}";
                }
                $column->setPropertyPath($this->mapPropertyPath($field));
            }
        }

        // TODO: Support query parameters
        $data = [];
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($builder->getQuery()->iterate([], $this->hydrationMode) as $result) {
            $entity = $result[0];
            $row = [];
            // TODO: Make adding ID optional
            //if ($addIdentifier) {
            $row['DT_RowId'] = $accessor->getValue($entity, $identifierPropertyPath);
            //}

            foreach ($state->getColumns() as $column) {
                $value = null === $column->getPropertyPath() || !$accessor->isReadable($entity, $column->getPropertyPath()) ? $column->getData() : $accessor->getValue($entity, $column->getPropertyPath());
                $row[$column->getName()] = $column->transform($entity, $value);
            }
            $data[] = $row;

            // Release memory by detaching from Doctrine
            $this->manager->detach($entity);
        }

        return new ArrayResultSet($data, $totalRecords, $displayRecords);
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
     * @param AbstractColumn[] $columns
     */
    protected function buildOrder($columns)
    {
        foreach ($columns as $column) {
            if ($column->isOrderable() && null !== $column->getOrderField() && null !== $column->getOrderDirection()) {
                $this->queryBuilder->addOrderBy($column->getOrderField(), $column->getOrderDirection());
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
                if (is_object($value)) {
                    return [$value];
                } elseif (!is_callable($value)) {
                    return $value;
                }

                return new class($value) implements QueryBuilderProcessorInterface {
                    private $callable;

                    public function __construct(callable $value)
                    {
                        $this->callable = $value;
                    }

                    public function process(QueryBuilder $queryBuilder, DataTableState $state)
                    {
                        return call_user_func($this->callable, $queryBuilder, $state);
                    }
                };
            });
    }

//
//    /**
//     * @param callable|ProcessorInterface $processor
//     * @param DataTableState $state
//     * @return mixed
//     */
//    private function process($processor, DataTableState $state)
//    {
//        if (is_callable($processor)) {
//            return $processor($this, $state);
//        } elseif ($processor instanceof ProcessorInterface) {
//            if ($processor instanceof QueryBuilderAwareInterface) {
//                $processor->setQueryBuilder($this->queryBuilder);
//            }
//
//            return $processor->process($this, $state);
//        } else {
//            $type = is_object($processor) ? get_class($processor) : gettype($processor);
//            throw new \LogicException('Expected Closure or ProcessorInterface, not ' . $type);
//        }
//    }
//
//
//
//    /**
//     * @param callable|ProcessorInterface $processor
//     * @param QueryBuilder $queryBuilder
//     * @param array $criteria
//     */
//    protected function runProcessor($processor, DataTableState $state, QueryBuilder &$queryBuilder = null, array &$criteria = [])
//    {
//        $result = $this->process($processor, $state);
//
//        if ($result instanceof QueryBuilder) {
//            $queryBuilder = $result;
//        } elseif ($result instanceof Criteria) {
//            $criteria[] = $result;
//        } elseif (null !== $result) {
//            throw new \LogicException('Unexpected processor result - expected QueryBuilder or Criteria or NULL');
//        }
//    }
//

//
//    public function handleState(DataTableState $state)
//    {
//        $this->createQueryBuilder($state);
//
//        /** @var Query\Expr\From $fromClause */
//        $fromClause = $this->queryBuilder->getDQLPart('from')[0];
//        $identifier = "{$fromClause->getAlias()}.{$this->metadata->getSingleIdentifierFieldName()}";
//
//        $this->totalRecords = $this->getCount($identifier);
//
//        $this->buildCriteria($state);
//
//        $this->displayRecords = $this->getCount($identifier);
//
//        $this->buildOrder($state->getColumns());
//
//        if ($state->getLength() > 0) {
//            $this->queryBuilder->setFirstResult($state->getStart())->setMaxResults($state->getLength());
//        }
//
//        /** @var Query\Expr\From $from */
//        foreach ($this->queryBuilder->getDQLPart('from') as $from) {
//            $this->aliases[$from->getAlias()] = [null, $this->manager->getMetadataFactory()->getMetadataFor($from->getFrom())];
//        }
//
//        foreach ($this->queryBuilder->getDQLPart('join') as $joins) {
//            /** @var Query\Expr\Join $join */
//            foreach ($joins as $join) {
//                list($origin, $target) = explode('.', $join->getJoin());
//
//                $mapping = $this->aliases[$origin][1]->getAssociationMapping($target);
//                $this->aliases[$join->getAlias()] = [$join->getJoin(), $this->manager->getMetadataFactory()->getMetadataFor($mapping['targetEntity'])];
//            }
//        }
//
//        $this->identifierPropertyPath = $this->mapPropertyPath($identifier);
//
//        foreach ($state->getColumns() as $column) {
//            if (null !== $column->getField() && null === $column->getPropertyPath()) {
//                $column->setPropertyPath($this->mapPropertyPath($column->getField()));
//            }
//        }
//
//        return $this;
//    }
//

//
//    public function getTotalRecords()
//    {
//        return $this->totalRecords;
//    }
//
//    public function getTotalDisplayRecords()
//    {
//        return $this->displayRecords;
//    }
//
////    public function getData()
////    {
////        return $this->queryBuilder->getQuery()->getResult($this->hydrationMode);
////    }
//
//    /**
//     * @param AbstractColumn[] $columns
//     * @param $row
//     * @param bool $addIdentifier
//     * @return mixed
//     */
//    public function mapRow($columns, $row, $addIdentifier = true)
//    {
//        $result = [];
//
//    }
}
