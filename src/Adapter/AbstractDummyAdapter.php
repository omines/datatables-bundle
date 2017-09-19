<?php
/**
 * Created by PhpStorm.
 * User: Robbert Beesems
 * Date: 9/14/2017
 * Time: 12:47 PM
 */

namespace Omines\DatatablesBundle\Adapter;

use Omines\DatatablesBundle\DatatableState;

abstract class AbstractDummyAdapter implements  AdapterInterface
{
    /** @var  DatatableState */
    private $state;

    function handleRequest(DatatableState $state)
    {
        $this->state = $state;
    }

    function getTotalRecords()
    {
        return 0;
    }

    function getTotalDisplayRecords()
    {
        return 0;
    }

    function getState()
    {
        return $this->state;
    }

    function mapRow($row)
    {
        $result = [];

        foreach ($this->state->getColumns() as $column) {
            $result[$column->getName()] = $column->getDefaultValue();
        }

        return $result;
    }
}