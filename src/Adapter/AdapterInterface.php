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

interface AdapterInterface
{
    /**
     * @param DatatableState $state
     */
    public function handleRequest(DatatableState $state);

    /**
     * get total records.
     *
     * @return int
     */
    public function getTotalRecords();

    /**
     * Get total records after filtering.
     *
     * @return int
     */
    public function getTotalDisplayRecords();

    /**
     * @return DatatableState
     */
    public function getState();

    /**
     * @return array
     */
    public function getData();

    /**
     * @param $row
     * @return mixed
     */
    public function mapRow($row);
}
