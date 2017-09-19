<?php

namespace Omines\DatatablesBundle\Adapter;

use Omines\DatatablesBundle\Datatable;
use Omines\DatatablesBundle\DatatableState;

interface AdapterInterface
{
    /**
     * @param DatatableState $state
     */
    function handleRequest(DatatableState $state);

    /**
     * get total records
     *
     * @return integer
     */
    function getTotalRecords();

    /**
     * get total records after filtering
     *
     * @return integer
     */
    function getTotalDisplayRecords();

    /**
     * @return DatatableState
     */
    function getState();

    /**
     * @return array
     */
    function getData();

    /**
     * @param $row
     * @return mixed
     */
    function mapRow($row);
}
