<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DatatablesBundle\Adapter;

use Omines\DatatablesBundle\DatatableState;

abstract class AbstractDummyAdapter implements AdapterInterface
{
    /** @var DatatableState */
    private $state;

    public function handleRequest(DatatableState $state)
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

    public function mapRow($row)
    {
        $result = [];

        foreach ($this->state->getColumns() as $column) {
            $result[$column->getName()] = $column->getDefaultValue();
        }

        return $result;
    }
}
