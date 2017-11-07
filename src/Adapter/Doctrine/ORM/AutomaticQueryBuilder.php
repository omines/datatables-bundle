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
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\DataTableState;

/**
 * AutomaticQueryBuilder.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 *
 * @todo Make stateless
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
     *
     * @param EntityManagerInterface $em
     * @param ClassMetadata $metadata
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
        foreach ($state->getDataTable()->getColumns() as $column) {
            $currentPart = $this->entityShortName;
            $currentAlias = $currentPart;
            $metadata = $this->metadata;
            $field = $column->getField();

            // TODO: Is this a good idea? Probably not in this form....
            if (!isset($field) && isset($this->metadata->fieldMappings[$column->getName()])) {
                $field = $this->entityShortName . '.' . $column->getName();
            }
            if (null !== $field) {
                $parts = explode('.', $field);

                if (count($parts) > 1 && $parts[0] === $this->entityShortName) {
                    array_shift($parts);
                }

                while (count($parts) > 1) {
                    $previousPart = $currentPart;
                    $previousAlias = $currentAlias;
                    $currentPart = array_shift($parts);
                    $currentAlias = ($previousPart === $this->entityShortName ? '' : $previousPart . '_') . $currentPart;

                    if (!array_key_exists($previousAlias . '.' . $currentPart, $this->joins)) {
                        $this->addJoin($previousAlias . '.' . $currentPart, $currentAlias, $column->getJoinType());
                    }

                    $metadata = $this->setIdentifierFromAssociation($currentAlias, $currentPart, $metadata);
                }

                $this->addSelectColumn($currentAlias, $this->getIdentifier($metadata));
                $this->addSelectColumn($currentAlias, $parts[0]);
            }
        }

        $builder->from($this->entityName, $this->entityShortName);
        $this->setSelectFrom($builder);
        $this->setJoins($builder);
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

    private function addJoin($columnTableName, $alias, $type)
    {
        $this->joins[$columnTableName] = [
            'alias' => $alias,
            'type' => $type,
        ];

        return $this;
    }

    private function getIdentifier(ClassMetadata $metadata)
    {
        $identifiers = $metadata->getIdentifierFieldNames();

        return array_shift($identifiers);
    }

    private function getMetadata($entityName)
    {
        try {
            $metadata = $this->em->getMetadataFactory()->getMetadataFor($entityName);
        } catch (MappingException $e) {
            throw new \Exception('DataTableQueryBuilder::getMetadata(): Given object ' . $entityName . ' is not a Doctrine Entity.');
        }

        return $metadata;
    }

    private function setIdentifierFromAssociation($association, $key, $metadata = null)
    {
        if (null === $metadata) {
            $metadata = $this->metadata;
        }

        $targetEntityClass = $metadata->getAssociationTargetClass($key);
        $targetMetadata = $this->getMetadata($targetEntityClass);
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
