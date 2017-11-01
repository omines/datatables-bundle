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

abstract class AbstractAdapter implements AdapterInterface
{
    /** @var DataTableState */
    private $state;

    public function handleRequest(DataTableState $state)
    {
        $this->state = $state;
    }

    public function getTotalRecords()
    {
        return 0;
    }

    public function getTotalDisplayRecords()
    {
        return 0;
    }

    public function getState()
    {
        return $this->state;
    }

    /**
     * {@inheritdoc}
     */
    public function mapRow($columns, $row, $addIdentifier)
    {
        $result = [];

        foreach ($columns as $column) {
            $result[$column->getName()] = $column->getDefaultValue();
        }

        return $result;
    }
}
