<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DataTablesBundle\Adapter;

use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\DataTableState;

interface AdapterInterface
{
    /**
     * @param DataTableState $state
     * @return AdapterInterface
     */
    public function handleState(DataTableState $state);

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
     * @return array
     */
    public function getData();

    /**
     * @param AbstractColumn[] $columns
     * @param $row
     * @param $addIdentifier
     * @return mixed
     */
    public function mapRow($columns, $row, $addIdentifier);
}
