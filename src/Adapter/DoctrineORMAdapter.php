<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DatatablesBundle\Adapter;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Omines\DatatablesBundle\Column\AbstractColumn;
use Omines\DatatablesBundle\Column\Column;
use Omines\DatatablesBundle\DatatableState;
use Omines\DatatablesBundle\Processor\Doctrine\Common\CriteriaProcessor;
use Omines\DatatablesBundle\Processor\Doctrine\ORM\QueryBuilderAwareInterface;
use Omines\DatatablesBundle\Processor\Doctrine\ORM\QueryBuilderProcessor;
use Omines\DatatablesBundle\Processor\ProcessorInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;

class DoctrineORMAdapter implements AdapterInterface
{
    /** @var int */
    private $hydrationMode;

    /** @var EntityManager */
    private $manager;

    /** @var ProcessorInterface[]|\Closure[] */
    private $queryProcessors;

    /** @var ProcessorInterface[]|\Closure[] */
    private $criteriaProcessors;

    private $metadata;

    /** @var QueryBuilder */
    private $queryBuilder;

    /** @var int */
    private $totalRecords;

    /** @var int */
    private $displayRecords;

    /** @var \Symfony\Component\PropertyAccess\PropertyAccessor */
    private $propertyAccessor;

    private $aliases;

    private $identifierPropertyPath;

    public function __construct(Registry $registry, $class, $hydrationMode = Query::HYDRATE_OBJECT, $queryProcessors = null, $criteriaProcessors = null)
    {
        $this->manager = $registry->getManagerForClass($class);
        $this->metadata = $this->manager->getClassMetadata($class);
        $this->hydrationMode = $hydrationMode;
        $this->queryProcessors = $queryProcessors;
        $this->criteriaProcessors = $criteriaProcessors;
        $this->displayRecords = 0;
        $this->totalRecords = 0;
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->aliases = [];

        if (null == $queryProcessors) {
            $this->queryProcessors[] = new QueryBuilderProcessor($this->manager, $this->metadata);
        }

        if (null == $criteriaProcessors) {
            $this->criteriaProcessors[] = new CriteriaProcessor();
        }
    }

    /**
     * @return EntityManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    private function process($processor, DatatableState $state)
    {
        if ($processor instanceof \Closure) {
            return $processor($this, $state);
        } elseif ($processor instanceof ProcessorInterface) {
            if ($processor instanceof QueryBuilderAwareInterface) {
                $processor->setQueryBuilder($this->queryBuilder);
            }

            return $processor->process($this, $state);
        } else {
            throw new \LogicException('Expected Closure or ProcessorInterface');
        }
    }

    protected function buildQuery(DatatableState $state)
    {
        $queryBuilder = null;
        $criteria = [];

        foreach ($this->queryProcessors as $processor) {
            $result = $this->process($processor, $state);

            if (null == $result) {
                continue;
            } elseif ($result instanceof QueryBuilder) {
                $queryBuilder = $result;
            } elseif ($result instanceof Criteria) {
                $criteria = $result;
            } else {
                throw new \LogicException("Can't handle processor result");
            }
        }

        if (null == $queryBuilder) {
            throw new \LogicException('Expected a queryBuilder');
        }
        $this->queryBuilder = $queryBuilder;

        foreach ($criteria as $c) {
            $this->queryBuilder->addCriteria($c);
        }
    }

    protected function buildCriteria(DatatableState $state)
    {
        $criteria = [];

        foreach ($this->criteriaProcessors as $processor) {
            $result = $this->process($processor, $state);

            if (null == $result) {
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
            if ($column->isOrderable() && null != $column->getOrderField() && null != $column->getOrderDirection()) {
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

            return $qb->getQuery()->getSingleScalarResult();
        } else {
            $qb->resetDQLPart('groupBy');
            $qb->select($qb->expr()->countDistinct($identifier));

            return $qb->getQuery()->getSingleScalarResult();
        }
    }

    public function handleState(DatatableState $state)
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
            if (null != $column->getField() && null == $column->getPropertyPath()) {
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
        list($origin, $target) = explode('.', $field);

        $path = [$target];
        $current = $this->aliases[$origin][0];

        while ($current != null) {
            list($origin, $target) = explode('.', $current);
            $path[] = $target;
            $current = $this->aliases[$origin][0];
        }

        if (Query::HYDRATE_ARRAY == $this->hydrationMode) {
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

        if ($addIdentifier)
            $result['DT_RowId'] = $this->propertyAccessor->getValue($row, $this->identifierPropertyPath);

        foreach ($columns as $column) {
            $result[$column->getName()] = null == $column->getPropertyPath() ? $column->getDefaultValue() : $this->propertyAccessor->getValue($row, $column->getPropertyPath());
        }

        return $result;
    }
}
