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

        $transformer = $state->getDataTable()->getTransformer();
        $accessor = PropertyAccess::createPropertyAccessor();
        $page = array_map(function ($result) use ($state, $transformer, $accessor) {
            $row = [];
            foreach ($state->getDataTable()->getColumns() as $column) {
                unset($propertyPath);
                if (empty($propertyPath = $column->getPropertyPath()) && !empty($field = $column->getField() ?? $column->getName())) {
                    $propertyPath = "[$field]";
                }
                $value = ($propertyPath && $accessor->isReadable($result, $propertyPath)) ? $accessor->getValue($result, $propertyPath) : null;
                $row[$column->getName()] = $column->transform($value, $result);
            }
            if ($transformer) {
                $row = call_user_func($transformer, $row, $result);
            }

            return $row;
        }, array_slice($this->data, $state->getStart(), $state->getLength()));

        return new ArrayResultSet($page, count($this->data));
    }
}
