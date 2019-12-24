<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Adapter\Doctrine\ORM;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\DataTableState;

/**
 * AutomaticQueryBuilder.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class AutomaticQueryBuilder implements QueryBuilderProcessorInterface
{
    /** @var EntityManagerInterface */
    private $em;

    /** @var ClassMetadata */
    private $metadata;

    /** @var string */
    private $entityName;

    /** @var string */
    private $entityShortName;

    /** @var array */
    private $selectColumns = [];

    /** @var array */
    private $joins = [];

    /**
     * AutomaticQueryBuilder constructor.
     */
    public function __construct(EntityManagerInterface $em, ClassMetadata $metadata)
    {
        $this->em = $em;
        $this->metadata = $metadata;

        $this->entityName = $this->metadata->getName();
        $this->entityShortName = mb_strtolower($this->metadata->getReflectionClass()->getShortName());
    }

    /**
     * {@inheritdoc}
     */
    public function process(QueryBuilder $builder, DataTableState $state)
    {
        if (empty($this->selectColumns) && empty($this->joins)) {
            foreach ($state->getDataTable()->getColumns() as $column) {
                $this->processColumn($column);
            }
        }
        $builder->from($this->entityName, $this->entityShortName);
        $this->setSelectFrom($builder);
        $this->setJoins($builder);
    }

    protected function processColumn(AbstractColumn $column)
    {
        $field = $column->getField();

        // Default to the column name if that corresponds to a field mapping
        if (!isset($field) && isset($this->metadata->fieldMappings[$column->getName()])) {
            $field = $column->getName();
        }
        if (null !== $field) {
            $this->addSelectColumns($column, $field);
        }
    }

    private function addSelectColumns(AbstractColumn $column, string $field)
    {
        $currentPart = $this->entityShortName;
        $currentAlias = $currentPart;
        $metadata = $this->metadata;

        $parts = explode('.', $field);

        if (count($parts) > 1 && $parts[0] === $currentPart) {
            array_shift($parts);
        }

        if (sizeof($parts) > 1 && $field = $metadata->hasField(implode('.', $parts))) {
            $this->addSelectColumn($currentAlias, implode('.', $parts));
        } else {
            while (count($parts) > 1) {
                $previousPart = $currentPart;
                $previousAlias = $currentAlias;
                $currentPart = array_shift($parts);
                $currentAlias = ($previousPart === $this->entityShortName ? '' : $previousPart . '_') . $currentPart;

                $this->joins[$previousAlias . '.' . $currentPart] = ['alias' => $currentAlias, 'type' => 'join'];

                $metadata = $this->setIdentifierFromAssociation($currentAlias, $currentPart, $metadata);
            }

            $this->addSelectColumn($currentAlias, $this->getIdentifier($metadata));
            $this->addSelectColumn($currentAlias, $parts[0]);
        }
    }

    private function addSelectColumn($columnTableName, $data)
    {
        if (isset($this->selectColumns[$columnTableName])) {
            if (!in_array($data, $this->selectColumns[$columnTableName], true)) {
                $this->selectColumns[$columnTableName][] = $data;
            }
        } else {
            $this->selectColumns[$columnTableName][] = $data;
        }

        return $this;
    }

    private function getIdentifier(ClassMetadata $metadata)
    {
        $identifiers = $metadata->getIdentifierFieldNames();

        return array_shift($identifiers);
    }

    private function setIdentifierFromAssociation(string $association, string $key, ClassMetadata $metadata)
    {
        $targetEntityClass = $metadata->getAssociationTargetClass($key);

        /** @var ClassMetadata $targetMetadata */
        $targetMetadata = $this->em->getMetadataFactory()->getMetadataFor($targetEntityClass);
        $this->addSelectColumn($association, $this->getIdentifier($targetMetadata));

        return $targetMetadata;
    }

    private function setSelectFrom(QueryBuilder $qb)
    {
        foreach ($this->selectColumns as $key => $value) {
            if (false === empty($key)) {
                $qb->addSelect('partial ' . $key . '.{' . implode(',', $value) . '}');
            } else {
                $qb->addSelect($value);
            }
        }

        return $this;
    }

    private function setJoins(QueryBuilder $qb)
    {
        foreach ($this->joins as $key => $value) {
            $qb->{$value['type']}($key, $value['alias']);
        }

        return $this;
    }
}
