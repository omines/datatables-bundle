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
    /** @var DataTable */
    private $dataTable;

    /** @var int */
    private $draw = 0;

    /** @var int */
    private $start = 0;

    /** @var int */
    private $length = -1;

    /** @var string */
    private $globalSearch = '';

    /** @var array */
    private $searchColumns = [];

    /** @var array */
    private $orderBy = [];

    /** @var bool */
    private $fromInitialRequest = false;

    /** @var mixed */
    protected $context;

    /**
     * DataTableState constructor.
     *
     * @param DataTable $dataTable
     */
    public function __construct(DataTable $dataTable)
    {
        $this->dataTable = $dataTable;
    }

    /**
     * @return DataTable
     */
    public function getDataTable(): DataTable
    {
        return $this->dataTable;
    }

    /**
     * @return mixed
     */
    public function getContext()
    {
        return $this->context;
    }

    /**
     * @param mixed $context
     * @return self
     */
    public function setContext($context): DataTableState
    {
        $this->context = $context;

        return $this;
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
     * @return $this
     */
    public function setDraw(int $draw)
    {
        $this->draw = $draw;

        return $this;
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
     * @return $this
     */
    public function setStart(int $start)
    {
        $this->start = $start;

        return $this;
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
     * @return $this
     */
    public function setLength(int $length)
    {
        $this->length = $length;

        return $this;
    }

    /**
     * @return string
     */
    public function getGlobalSearch(): string
    {
        return $this->globalSearch;
    }

    /**
     * @param string $globalSearch
     * @return $this
     */
    public function setGlobalSearch(string $globalSearch)
    {
        $this->globalSearch = $globalSearch;

        return $this;
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
     * @return $this
     */
    public function setFromInitialRequest(bool $fromInitialRequest)
    {
        $this->fromInitialRequest = $fromInitialRequest;

        return $this;
    }

    /**
     * @param AbstractColumn $column
     * @param string $direction
     * @return $this
     */
    public function addOrderBy(AbstractColumn $column, string $direction = DataTable::SORT_ASCENDING)
    {
        $this->orderBy[] = [$column, $direction];

        return $this;
    }

    /**
     * @return array
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * @param array $orderBy
     * @return self
     */
    public function setOrderBy(array $orderBy = []): self
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * Returns an array of column-level searches.
     * @return array
     */
    public function getSearchColumns(): array
    {
        return $this->searchColumns;
    }

    /**
     * @param AbstractColumn $column
     * @param string $search
     * @param bool $isRegex
     * @return self
     */
    public function setColumnSearch(AbstractColumn $column, string $search, bool $isRegex = false): self
    {
        $this->searchColumns[$column->getName()] = [$column, $search, $isRegex];

        return $this;
    }
}
