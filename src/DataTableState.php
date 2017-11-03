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
use Omines\DataTablesBundle\Column\ColumnState;

/**
 * DataTableState.
 *
 * @author Robbert Beesems <robbert.beesems@omines.com>
 */
class DataTableState
{
    /** @var DataTable */
    private $dataTable;

    /** @var int */
    private $draw;

    /** @var int */
    private $start;

    /** @var int */
    private $length;

    /** @var ColumnState[] */
    private $columns;

    /** @var string */
    private $search;

    /** @var bool */
    private $fromInitialRequest = false;

    /**
     * DataTableState constructor.
     *
     * @param DataTable $dataTable
     * @param int $start
     * @param int $length
     * @param ColumnState[] $columns
     * @param string $search
     */
    public function __construct(DataTable $dataTable, int $start = 0, int $length = -1, array $columns = [], string $search = '')
    {
        $this->dataTable = $dataTable;
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
    public function getColumns(): array
    {
        return $this->dataTable->getColumns();
    }

    /**
     * @return ColumnState[]
     */
    public function getColumnStates(): array
    {
        return $this->columns;
    }

    /**
     * @param AbstractColumn $column
     */
    public function addColumn(AbstractColumn $column)
    {
        $this->columns[$column->getName()] = new ColumnState($column);
    }

    /**
     * @param int $index
     * @return AbstractColumn
     */
    public function getColumnState(int $index): AbstractColumn
    {
        if ($index < 0 || $index >= count($this->columns)) {
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
