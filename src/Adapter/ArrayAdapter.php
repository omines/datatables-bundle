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

/**
 * ArrayAdapter.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class ArrayAdapter implements AdapterInterface
{
    /** @var array */
    private $data = [];

    /**
     * {@inheritdoc}
     */
    public function configure(array $options)
    {
        $this->data = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(DataTableState $state): ResultSetInterface
    {
        // TODO: Apply search

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

        return new ArrayResultSet(iterator_to_array($this->processData($state, $page, $map)), count($this->data));
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
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach ($data as $result) {
            $row = [];
            foreach ($state->getDataTable()->getColumns() as $column) {
                $value = (!empty($propertyPath = $map[$column->getName()]) && $accessor->isReadable($result, $propertyPath)) ? $accessor->getValue($result, $propertyPath) : null;
                $row[$column->getName()] = $column->transform($value, $result);
            }
            if ($transformer) {
                $row = call_user_func($transformer, $row, $result);
            }
            yield $row;
        }
    }
}
