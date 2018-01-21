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
        $length = $state->getLength();
        $page = $length > 0 ? array_slice($this->data, $state->getStart(), $state->getLength()) : $this->data;
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

        $data = iterator_to_array($this->processData($state, $page, $map));

        return new ArrayResultSet($data, count($this->data), count($data));
    }

    /**
     * @param DataTableState $state
     * @param array $data
     * @param array $map
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
     * @param DataTableState $state
     * @param array $result
     * @param array $map
     * @param string $search
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
            $row[$column->getName()] = $column->transform($value, $result);
        }

        return $match ? $row : null;
    }
}
