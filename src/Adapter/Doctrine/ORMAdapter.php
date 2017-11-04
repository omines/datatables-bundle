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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\AdapterInterface;
use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\DataTableState;
use Omines\DataTablesBundle\Processor\Doctrine\Common\CriteriaProcessor;
use Omines\DataTablesBundle\Processor\Doctrine\ORM\QueryBuilderAwareInterface;
use Omines\DataTablesBundle\Processor\Doctrine\ORM\QueryBuilderProcessor;
use Omines\DataTablesBundle\Processor\ProcessorInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

class ORMAdapter implements AdapterInterface
{
    /** @var Registry */
    private $registry;

    /** @var int */
    private $hydrationMode;

    /** @var EntityManager */
    private $manager;

    /** @var ProcessorInterface[]|\Closure[] */
    private $queryProcessors;

    /** @var ProcessorInterface[]|\Closure[] */
    private $criteriaProcessors;

    /** @var \Doctrine\Common\Persistence\Mapping\ClassMetadata|ClassMetadata */
    private $metadata;

    /** @var QueryBuilder */
    private $queryBuilder;

    /** @var int */
    private $totalRecords;

    /** @var int */
    private $displayRecords;

    /** @var \Symfony\Component\PropertyAccess\PropertyAccessor */
    private $propertyAccessor;

    /** @var array */
    private $aliases;

    /** @var string */
    private $identifierPropertyPath;

    /**
     * DoctrineORMAdapter constructor.
     *
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
        $this->registry = $registry;

        $this->displayRecords = 0;
        $this->totalRecords = 0;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->aliases = [];
    }

    /**
     * {@inheritdoc}
     */
    public function configure(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        if (isset($options['entity'])) {
            if (null === ($this->manager = $this->registry->getManagerForClass($options['entity']))) {
                throw new \LogicException(sprintf('There is no manager for entity "%s"', $options['entity']));
            }
            $this->metadata = $this->manager->getClassMetadata($options['entity']);
        } else {
            $this->manager = $this->registry->getManager();
            $this->metadata = null;
        }

        $this->hydrationMode = $options['hydrate'];
        $this->queryProcessors = (array) $options['query'];
        $this->criteriaProcessors = (array) $options['criteria'];

        if (empty($this->queryProcessors)) {
            if (!$this->metadata) {
                throw new \LogicException("You must provide either the 'entity' option, or at least one Query Processor in the 'query' option");
            }
            $this->queryProcessors = [new QueryBuilderProcessor($this->manager, $this->metadata)];
        }

        if (empty($this->criteriaProcessors)) {
            $this->criteriaProcessors = [new CriteriaProcessor()];
        }
    }

    /**
     * @param callable|ProcessorInterface $processor
     * @param DataTableState $state
     * @return mixed
     */
    private function process($processor, DataTableState $state)
    {
        if (is_callable($processor)) {
            return $processor($this, $state);
        } elseif ($processor instanceof ProcessorInterface) {
            if ($processor instanceof QueryBuilderAwareInterface) {
                $processor->setQueryBuilder($this->queryBuilder);
            }

            return $processor->process($this, $state);
        } else {
            $type = is_object($processor) ? get_class($processor) : gettype($processor);
            throw new \LogicException('Expected Closure or ProcessorInterface, not ' . $type);
        }
    }

    /**
     * @param DataTableState $state
     */
    protected function buildQuery(DataTableState $state)
    {
        $queryBuilder = null;
        $criteria = [];

        foreach ($this->queryProcessors as $processor) {
            $this->runProcessor($processor, $state, $queryBuilder, $criteria);
        }

        if (null === $queryBuilder) {
            throw new \LogicException('Query processors must yield an instance of QueryBuilder');
        }
        $this->queryBuilder = $queryBuilder;

        foreach ($criteria as $c) {
            $this->queryBuilder->addCriteria($c);
        }
    }

    /**
     * @param callable|ProcessorInterface $processor
     * @param QueryBuilder $queryBuilder
     * @param array $criteria
     */
    protected function runProcessor($processor, DataTableState $state, QueryBuilder &$queryBuilder = null, array &$criteria = [])
    {
        $result = $this->process($processor, $state);

        if ($result instanceof QueryBuilder) {
            $queryBuilder = $result;
        } elseif ($result instanceof Criteria) {
            $criteria[] = $result;
        } elseif (null !== $result) {
            throw new \LogicException('Unexpected processor result - expected QueryBuilder or Criteria or NULL');
        }
    }

    /**
     * @param DataTableState $state
     */
    protected function buildCriteria(DataTableState $state)
    {
        $criteria = [];

        foreach ($this->criteriaProcessors as $processor) {
            $result = $this->process($processor, $state);

            if (null === $result) {
                continue;
            } elseif ($result instanceof Criteria) {
                $criteria[] = $result;
            } else {
                throw new \LogicException("Can't handle processor result");
            }
        }

        foreach ($criteria as $c) {
            $this->queryBuilder->addCriteria($c);
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

    protected function getCount($identifier)
    {
        $qb = clone $this->queryBuilder;

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

    public function handleState(DataTableState $state)
    {
        $this->buildQuery($state);

        /** @var Query\Expr\From $fromClause */
        $fromClause = $this->queryBuilder->getDQLPart('from')[0];
        $identifier = "{$fromClause->getAlias()}.{$this->metadata->getSingleIdentifierFieldName()}";

        $this->totalRecords = $this->getCount($identifier);

        $this->buildCriteria($state);

        $this->displayRecords = $this->getCount($identifier);

        $this->buildOrder($state->getColumns());

        if ($state->getLength() > 0) {
            $this->queryBuilder->setFirstResult($state->getStart())->setMaxResults($state->getLength());
        }

        /** @var Query\Expr\From $from */
        foreach ($this->queryBuilder->getDQLPart('from') as $from) {
            $this->aliases[$from->getAlias()] = [null, $this->manager->getMetadataFactory()->getMetadataFor($from->getFrom())];
        }

        foreach ($this->queryBuilder->getDQLPart('join') as $joins) {
            /** @var Query\Expr\Join $join */
            foreach ($joins as $join) {
                list($origin, $target) = explode('.', $join->getJoin());

                $mapping = $this->aliases[$origin][1]->getAssociationMapping($target);
                $this->aliases[$join->getAlias()] = [$join->getJoin(), $this->manager->getMetadataFactory()->getMetadataFor($mapping['targetEntity'])];
            }
        }

        $this->identifierPropertyPath = $this->mapPropertyPath($identifier);

        foreach ($state->getColumns() as $column) {
            if (null !== $column->getField() && null === $column->getPropertyPath()) {
                $column->setPropertyPath($this->mapPropertyPath($column->getField()));
            }
        }

        return $this;
    }

    /**
     * @param string $field
     * @return string
     */
    private function mapPropertyPath($field)
    {
        $parts = explode('.', $field);
        if (count($parts) < 2) {
            throw new \RuntimeException(sprintf('Field name "%s" must consist at least of an alias and a field separated with a period', $field));
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

    public function getTotalRecords()
    {
        return $this->totalRecords;
    }

    public function getTotalDisplayRecords()
    {
        return $this->displayRecords;
    }

    public function getData()
    {
        return $this->queryBuilder->getQuery()->getResult($this->hydrationMode);
    }

    /**
     * @param AbstractColumn[] $columns
     * @param $row
     * @param bool $addIdentifier
     * @return mixed
     */
    public function mapRow($columns, $row, $addIdentifier = true)
    {
        $result = [];

        if ($addIdentifier) {
            $result['DT_RowId'] = $this->propertyAccessor->getValue($row, $this->identifierPropertyPath);
        }

        foreach ($columns as $column) {
            $result[$column->getName()] = null === $column->getPropertyPath() || !$this->propertyAccessor->isReadable($row, $column->getPropertyPath()) ? $column->getDefaultValue() : $this->propertyAccessor->getValue($row, $column->getPropertyPath());
        }

        return $result;
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'entity' => null,
            'hydrate' => Query::HYDRATE_OBJECT,
            'query' => [],
            'criteria' => [],
        ])
            ->setAllowedTypes('entity', ['string', 'null'])
            ->setAllowedTypes('hydrate', 'int')
            ->setAllowedTypes('query', ['array', 'class'])
            ->setAllowedTypes('criteria', ['array', 'class'])
        ;
    }
}
