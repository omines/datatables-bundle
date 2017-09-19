<?php
/**
 * Created by PhpStorm.
 * User: robbert
 * Date: 9/10/17
 * Time: 11:49 PM
 */

namespace Omines\DatatablesBundle\Processor\Doctrine\ORM;

use Doctrine\Common\Persistence\Mapping\ClassMetadata;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\ORM\QueryBuilder;
use Omines\DatatablesBundle\Adapter\AdapterInterface;
use Omines\DatatablesBundle\Processor\ProcessorInterface;

class QueryBuilderProcessor implements ProcessorInterface
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var ClassMetadata
     */
    private $metadata;

    /**
     * @var string
     */
    private $entityName;
    /**
     * @var string
     */
    private $entityShortName;

    /**
     * @var mixed
     */
    private $rootEntityIdentifier;

    /**
     * @var array
     */
    private $selectColumns;

    /**
     * @var array
     */
    private $joins;


    public function __construct(EntityManager $manager, ClassMetadata $metadata)
    {
        $this->em = $manager;
        $this->metadata = $metadata;
        $this->selectColumns = [];
        $this->joins = [];
        $this->entityName = $this->metadata->getName();
        $this->entityShortName = strtolower($this->metadata->getReflectionClass()->getShortName());
        $this->rootEntityIdentifier = $this->getIdentifier($this->metadata);
    }

    private function setSelectFrom(QueryBuilder $qb)
    {
        foreach ($this->selectColumns as $key => $value) {
            if (false === empty($key)) {
                $qb->addSelect('partial '.$key.'.{'.implode(',', $value).'}');
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

    private function addSelectColumn($columnTableName, $data)
    {
        if (isset($this->selectColumns[$columnTableName])) {
            if (!in_array($data, $this->selectColumns[$columnTableName])) {
                $this->selectColumns[$columnTableName][] = $data;
            }
        } else {
            $this->selectColumns[$columnTableName][] = $data;
        }
        return $this;
    }

    private function addJoin($columnTableName, $alias, $type)
    {
        $this->joins[$columnTableName] = array(
            'alias' => $alias,
            'type' => $type,
        );
        return $this;
    }

    private function getMetadata($entityName)
    {
        try {
            $metadata = $this->em->getMetadataFactory()->getMetadataFor($entityName);
        } catch (MappingException $e) {
            throw new \Exception('DatatableQueryBuilder::getMetadata(): Given object '.$entityName.' is not a Doctrine Entity.');
        }

        return $metadata;
    }

    private function getIdentifier(ClassMetadata $metadata)
    {
        $identifiers = $metadata->getIdentifierFieldNames();
        return array_shift($identifiers);
    }

    public function process(AdapterInterface $adapter)
    {
        foreach ($adapter->getState()->getColumns() as $key => $column) {
            $currentPart = $this->entityShortName;
            $currentAlias = $currentPart;
            $metadata = $this->metadata;

            if ($column->getField() != null) {
                $parts = explode('.', $column->getField());

                if(count($parts) > 1 && $parts[0] == $this->entityShortName)
                    array_shift($parts);

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

        $qb = $this->em->createQueryBuilder()->from($this->entityName, $this->entityShortName);

        $this->setSelectFrom($qb);
        $this->setJoins($qb);

        return $qb;
    }
}