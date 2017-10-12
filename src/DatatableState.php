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
    private $draw;
    /** @var int */
    private $start;
    /** @var int */
    private $length;
    /** @var AbstractColumn[] */
    private $columns;
    /** @var array */
    private $search;
    /** @var  boolean */
    private $fromInitialRequest;

    public function __construct($start = 0, $length = -1, $columns = [], $search = "")
    {
        $this->draw = 0;
        $this->start = $start;
        $this->length = $length;
        $this->columns = $columns;
        $this->search = $search;
    }

    /**
     * @return int
     */
    public function getDraw()
    {
        return $this->draw;
    }

    /**
     * @param int $draw
     */
    public function setDraw($draw)
    {
        $this->draw = $draw;
    }

    /**
     * @return int
     */
    public function getStart()
    {
        return $this->start;
    }

    /**
     * @param int $start
     */
    public function setStart($start)
    {
        $this->start = $start;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @param int $length
     */
    public function setLength($length)
    {
        $this->length = $length;
    }

    /**
     * @return array
     */
    public function getSearch()
    {
        return $this->search;
    }

    /**
     * @param array $search
     */
    public function setSearch($search)
    {
        $this->search = $search;
    }

    /**
     * @return AbstractColumn[]
     */
    public function getColumns()
    {
        return $this->columns;
    }

    public function addColumn(AbstractColumn $column)
    {
        $this->columns[] = $column;
    }

    /**
     * @param $index
     * @return AbstractColumn
     */
    public function getColumn($index)
    {
        return $this->columns[$index];
    }

    /**
     * @return bool
     */
    public function isFromInitialRequest()
    {
        return $this->fromInitialRequest;
    }

    /**
     * @param bool $fromInitialRequest
     */
    public function setFromInitialRequest($fromInitialRequest)
    {
        $this->fromInitialRequest = $fromInitialRequest;
    }
}
