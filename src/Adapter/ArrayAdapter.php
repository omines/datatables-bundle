<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Adapter;

use Omines\DataTablesBundle\DataTableState;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * ArrayAdapter.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class ArrayAdapter implements AdapterInterface
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
        // very basic implementation of sorting
        try {
            $oc = $state->getOrderBy()[0][0]->getName();
            $oo = \mb_strtolower($state->getOrderBy()[0][1]);

            \usort($this->data, function ($a, $b) use ($oc, $oo) {
                if ('desc' === $oo) {
                    return $b[$oc] <=> $a[$oc];
                }

                return $a[$oc] <=> $b[$oc];
            });
        } catch (\Throwable $exception) {
            // ignore exception
        }

        $map = $this->getPropertyMap($state);
        
        $data = iterator_to_array($this->processData($state, $this->data, $map));

        $length = $state->getLength();
        $page = $length > 0 ? array_slice($data, $state->getStart(), $state->getLength()) : $data;

        return new ArrayResultSet($page, count($this->data), count($data));
    }

    protected function getPropertyMap($state): array
    {
        $map = [];
        foreach ($state->getDataTable()->getColumns() as $column) {
            unset($propertyPath);
            if (empty($propertyPath = $column->getPropertyPath()) && !empty($field = $column->getField() ?? $column->getName())) {
                $propertyPath = "[$field]";
            }
            if (null !== $propertyPath) {
                $map[$column->getName()] = $propertyPath;
            }
        }
        return $map;
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
    protected function processRow(DataTableState $state, array $result, array $map, string $search)
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
