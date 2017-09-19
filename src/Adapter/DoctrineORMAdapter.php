<?php

namespace Omines\DatatablesBundle\Adapter;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Criteria;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Omines\DatatablesBundle\DatatableState;
use Omines\DatatablesBundle\Processor\Doctrine\Common\CriteriaProcessor;
use Omines\DatatablesBundle\Processor\Doctrine\ORM\QueryBuilderAwareInterface;
use Omines\DatatablesBundle\Processor\Doctrine\ORM\QueryBuilderProcessor;
use Omines\DatatablesBundle\Processor\ProcessorInterface;
use Symfony\Component\Form\Exception\LogicException;
use Symfony\Component\PropertyAccess\PropertyAccess;

class DoctrineORMAdapter implements AdapterInterface
{
    /** @var int */
    private $hydrationMode;

    /** @var EntityManager */
    private $manager;

    /** @var  ProcessorInterface[]|\Closure[] */
    private $queryProcessors;

    /** @var  ProcessorInterface[]|\Closure[] */
    private $criteriaProcessors;

    private $metadata;

    /** @var  QueryBuilder */
    private $queryBuilder;

    /** @var int */
    private $totalRecords;

    /** @var int */
    private $displayRecords;

    /** @var \Symfony\Component\PropertyAccess\PropertyAccessor */
    private $propertyAccessor;

    /** @var  DatatableState */
    private $state;

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

        if($queryProcessors == null)
            $this->queryProcessors[] = new QueryBuilderProcessor($this->manager, $this->metadata);

        if($criteriaProcessors == null)
            $this->criteriaProcessors[] = new CriteriaProcessor();
    }

    /**
     * @return EntityManager
     */
    public function getManager()
    {
        return $this->manager;
    }

    /**
     * @return DatatableState
     */
    public function getState()
    {
        return $this->state;
    }

    private function process($processor)
    {
        if ($processor instanceof \Closure)
            return $processor($this);
        elseif ($processor instanceof ProcessorInterface) {
            if($processor instanceof QueryBuilderAwareInterface)
                $processor->setQueryBuilder($this->queryBuilder);

            return $processor->process($this);
        }
        else
            throw new LogicException("Expected Closure or ProcessorInterface");
    }

    protected function buildQuery()
    {
        $queryBuilder = null;
        $criteria = [];

        foreach ($this->queryProcessors as $processor) {
            $result = $this->process($processor);

            if ($result == null) {
                continue;
            } elseif ($result instanceof QueryBuilder) {
                $queryBuilder = $result;
            } elseif ($result instanceof Criteria) {
                $criteria = $result;
            } else {
                throw new LogicException("Can't handle processor result");
            }
        }

        if ($queryBuilder == null)
            throw new LogicException("Expected a queryBuilder");

        $this->queryBuilder = $queryBuilder;

        foreach ($criteria as $c)
            $this->queryBuilder->addCriteria($c);
    }

    protected function buildCriteria()
    {
        $criteria = [];

        foreach ($this->criteriaProcessors as $processor) {
            $result = $this->process($processor);

            if ($result == null) {
                continue;
            } elseif ($result instanceof Criteria) {
                $criteria[] = $result;
            } else {
                throw new LogicException("Can't handle processor result");
            }
        }

        foreach ($criteria as $c)
            $this->queryBuilder->addCriteria($c);
    }

    protected function buildOrder()
    {
        foreach ($this->state->getColumns() as $column) {
            if ($column->isOrderable() && $column->getOrderField() != null && $column->getOrderDirection() != null) {
                $this->queryBuilder->addOrderBy($column->getOrderField(), $column->getOrderDirection());
            }
        }
    }

    protected function getCount($identifier)
    {
        $qb = clone $this->queryBuilder;

        $qb->resetDQLPart('orderBy');
        $gb = $qb->getDQLPart('groupBy');
        if (empty($gb) || !in_array($identifier, $gb)) {
            $qb->select($qb->expr()->count($identifier));

            return $qb->getQuery()->getSingleScalarResult();
        } else {
            $qb->resetDQLPart('groupBy');
            $qb->select($qb->expr()->countDistinct($identifier));

            return $qb->getQuery()->getSingleScalarResult();
        }
    }

    private function mapPartsToPropertyPath(array $parts)
    {
        if ($this->hydrationMode == Query::HYDRATE_ARRAY) {
            return '[' . implode('][', $parts) . ']';
        } else {
            return implode('.', $parts);
        }
    }

    public function handleRequest(DatatableState $state)
    {
        $this->state = $state;

        $this->buildQuery();

        /** @var Query\Expr\From $fromClause */
        $fromClause = $this->queryBuilder->getDQLPart('from')[0];
        $identifier = "{$fromClause->getAlias()}.{$this->metadata->getSingleIdentifierColumnName()}";

        $this->totalRecords = $this->getCount($identifier);

        $this->buildCriteria();

        $this->displayRecords = $this->getCount($identifier);

        $this->buildOrder();

        if ($state->getLength() > 0) {
            $this->queryBuilder->setFirstResult($state->getStart())->setMaxResults($state->getLength());
        }

        //Determine mapping
        foreach ($state->getColumns() as $column) {
            if ($column->getField() != null && $column->getPropertyPath() == null) {
                $path = null;
                $parts = explode('.', $column->getField());

                if ($parts[0] == $fromClause->getAlias())
                    array_shift($parts);

                $column->setPropertyPath($this->mapPartsToPropertyPath($parts));
            }
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

    public function mapRow($row)
    {
        $result['DT_RowId'] = $this->propertyAccessor->getValue($row, $this->mapPartsToPropertyPath([$this->metadata->getSingleIdentifierColumnName()]));

        foreach ($this->state->getColumns() as $column) {
            $result[$column->getName()] = $column->getPropertyPath() == null ? $column->getDefaultValue() : $this->propertyAccessor->getValue($row, $column->getPropertyPath());
        }

        return $result;
    }
}
