<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DatatablesBundle;

use Omines\DatatablesBundle\Column\AbstractColumn;

class DatatableState
{
    /** @var int */
    private $start;
    /** @var int */
    private $length;
    /** @var AbstractColumn[] */
    private $columns;
    /** @var array */
    private $search;

    public function __construct($start, $length, $columns, $search)
    {
        $this->start = $start;
        $this->length = $length;
        $this->columns = $columns;
        $this->search = $search;
    }

    /**
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @return array
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * @return AbstractColumn[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @param $index
     * @return AbstractColumn
     */
    public function getColumn($index)
    {
        return $this->columns[$index];
    }
}
