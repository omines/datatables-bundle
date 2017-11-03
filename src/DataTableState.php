<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle;

use Omines\DataTablesBundle\Column\AbstractColumn;

/**
 * DataTableState.
 *
 * @author Robbert Beesems <robbert.beesems@omines.com>
 */
class DataTableState
{
    /** @var int */
    private $draw;

    /** @var int */
    private $start;

    /** @var int */
    private $length;

    /** @var AbstractColumn[] */
    private $columns;

    /** @var string */
    private $search;

    /** @var bool */
    private $fromInitialRequest = false;

    /**
     * DataTableState constructor.
     *
     * @param int $start
     * @param int $length
     * @param array $columns
     * @param string $search
     */
    public function __construct($start = 0, $length = -1, $columns = [], $search = '')
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
    public function getDraw(): int
    {
        return $this->draw;
    }

    /**
     * @param int $draw
     */
    public function setDraw(int $draw)
    {
        $this->draw = $draw;
    }

    /**
     * @return int
     */
    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * @param int $start
     */
    public function setStart(int $start)
    {
        $this->start = $start;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @param int $length
     */
    public function setLength(int $length)
    {
        $this->length = $length;
    }

    /**
     * @return string
     */
    public function getSearch(): string
    {
        return $this->search;
    }

    /**
     * @param string $search
     */
    public function setSearch(string $search)
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

    /**
     * @param AbstractColumn $column
     */
    public function addColumn(AbstractColumn $column)
    {
        $this->columns[] = $column;
    }

    /**
     * @param int $index
     * @return AbstractColumn
     */
    public function getColumn(int $index): AbstractColumn
    {
        if ($index < 0 || $index > count($this->columns)) {
            throw new \InvalidArgumentException(sprintf('There is no column with index %d', $index));
        }

        return $this->columns[$index];
    }

    /**
     * @return bool
     */
    public function isFromInitialRequest(): bool
    {
        return $this->fromInitialRequest;
    }

    /**
     * @param bool $fromInitialRequest
     */
    public function setFromInitialRequest(bool $fromInitialRequest)
    {
        $this->fromInitialRequest = $fromInitialRequest;
    }
}
