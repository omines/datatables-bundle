<?php

declare(strict_types=1);

namespace Omines\DataTablesBundle\Adapter;

use Omines\DataTablesBundle\DataTableState;
use Omines\DataTablesBundle\Adapter\ArrayResultSet;
use Omines\DataTablesBundle\Adapter\AdapterInterface;
use Omines\DataTablesBundle\Adapter\ResultSetInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * ArrayObjectAdapter mainly based on ArrayAdapter
 *
 * @author Jens Zahner <jens.zahner@sr-travel.de>
 */
class ArrayObjectAdapter implements AdapterInterface
{
    /** @var array */
    private $data = [];
    
    /** @var PropertyAccessor */
    private $accessor;
    
    /**
     * {@inheritdoc}
     */
    public function configure(array $options)
    {
        $this->data = $options;
        $this->accessor = PropertyAccess::createPropertyAccessor();
    }
    
    /**
     * {@inheritdoc}
     */
    public function getData(DataTableState $state): ResultSetInterface
    {
        $map = [];
        foreach ($state->getDataTable()->getColumns() as $column) {
            if (!empty($propertyPath = $column->getPropertyPath())) {
                $map[$column->getName()] = $propertyPath;
            }
            elseif (!empty($field = $column->getField())) {
                $map[$column->getName()] = $field;
            }
            else {
                $map[$column->getName()] = $column->getName();
            }
        }
        // very basic implementation of sorting
        try {
            $orderColumn = $state->getOrderBy()[0][0]->getName();
            $orderDirectionDesc = (bool) (\mb_strtolower($state->getOrderBy()[0][1]))=='desc';

            $orderField = $map[$orderColumn];
            
            \usort($this->data, function ($a, $b) use ($orderField, $orderDirectionDesc) {
                if ($orderDirectionDesc) {
                    return $this->accessor->getValue($b, $orderField) <=> $this->accessor->getValue($a, $orderField);
                }
                return $this->accessor->getValue($a, $orderField) <=> $this->accessor->getValue($b, $orderField);
            });
        } catch (\Throwable $exception) {
            // ignore exception
        }

        $data = iterator_to_array($this->processData($state, $this->data, $map));
        
        $length = $state->getLength();
        $page = $length > 0 ? array_slice($data, $state->getStart(), $state->getLength()) : $data;
        
        return new ArrayResultSet($page, count($this->data), count($data));
    }
    
    /**
     * @return \Generator
     */
    protected function processData(DataTableState $state, array $data, array $map)
    {
        $transformer = $state->getDataTable()->getTransformer();
        $search = $state->getGlobalSearch() ?: '';
        foreach ($data as $result) {
            if ($row = $this->processRow($state, $result, $map, $search)) {
                if (null !== $transformer) {
                    $row = call_user_func($transformer, $row, $result);
                }
                yield $row;
            }
        }
    }
    
    /**
     * @return array|null
     */
    protected function processRow(DataTableState $state, $result, array $map, string $search)
    {
        $row = [];
        $match = empty($search);
        foreach ($state->getDataTable()->getColumns() as $column) {
            $value = (!empty($propertyPath = $map[$column->getName()]) && $this->accessor->isReadable($result, $propertyPath)) ? $this->accessor->getValue($result, $propertyPath) : null;
            $value = $column->transform($value, $result);
            if (!$match) {
                $match = (false !== mb_stripos($value, $search));
            }
            $row[$column->getName()] = $value;
        }
        
        return $match ? $row : null;
    }
}
