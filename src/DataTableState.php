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
use Symfony\Component\HttpFoundation\ParameterBag;

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
    private $isInitial = false;

    /** @var bool */
    private $isCallback = false;

    /** @var string */
    private $exporterName = null;

    /**
     * DataTableState constructor.
     */
    public function __construct(DataTable $dataTable)
    {
        $this->dataTable = $dataTable;
    }

    /**
     * Constructs a state based on the default options.
     *
     * @return DataTableState
     */
    public static function fromDefaults(DataTable $dataTable)
    {
        $state = new self($dataTable);
        $state->start = (int) $dataTable->getOption('start');
        $state->length = (int) $dataTable->getOption('pageLength');

        foreach ($dataTable->getOption('order') as $order) {
            $state->addOrderBy($dataTable->getColumn($order[0]), $order[1]);
        }

        return $state;
    }

    /**
     * Loads datatables state from a parameter bag on top of any existing settings.
     */
    public function applyParameters(ParameterBag $parameters)
    {
        $this->draw = $parameters->getInt('draw');
        $this->isCallback = true;
        $this->isInitial = $parameters->getBoolean('_init', false);
        $this->exporterName = $parameters->get('_exporter');

        $this->start = (int) $parameters->get('start', $this->start);
        $this->length = (int) $parameters->get('length', $this->length);

        $search = $parameters->all()['search'] ?? [];
        $this->setGlobalSearch($search['value'] ?? $this->globalSearch);

        $this->handleOrderBy($parameters);
        $this->handleSearch($parameters);
    }

    private function handleOrderBy(ParameterBag $parameters)
    {
        if ($parameters->has('order')) {
            $this->orderBy = [];
            foreach ($parameters->all()['order'] ?? [] as $order) {
                $column = $this->getDataTable()->getColumn((int) $order['column']);
                $this->addOrderBy($column, $order['dir'] ?? DataTable::SORT_ASCENDING);
            }
        }
    }

    private function handleSearch(ParameterBag $parameters)
    {
        foreach ($parameters->all()['columns'] ?? [] as $key => $search) {
            $column = $this->dataTable->getColumn((int) $key);
            $value = $this->isInitial ? $search : $search['search']['value'];

            if ($column->isSearchable() && ('' !== trim($value))) {
                $this->setColumnSearch($column, $value);
            }
        }
    }

    public function isInitial(): bool
    {
        return $this->isInitial;
    }

    public function isCallback(): bool
    {
        return $this->isCallback;
    }

    public function getDataTable(): DataTable
    {
        return $this->dataTable;
    }

    public function getDraw(): int
    {
        return $this->draw;
    }

    public function getStart(): int
    {
        return $this->start;
    }

    /**
     * @return $this
     */
    public function setStart(int $start)
    {
        $this->start = $start;

        return $this;
    }

    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @return $this
     */
    public function setLength(int $length)
    {
        $this->length = $length;

        return $this;
    }

    public function getGlobalSearch(): string
    {
        return $this->globalSearch;
    }

    /**
     * @return $this
     */
    public function setGlobalSearch(string $globalSearch)
    {
        $this->globalSearch = $globalSearch;

        return $this;
    }

    /**
     * @return $this
     */
    public function addOrderBy(AbstractColumn $column, string $direction = DataTable::SORT_ASCENDING)
    {
        $this->orderBy[] = [$column, $direction];

        return $this;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * @return $this
     */
    public function setOrderBy(array $orderBy = []): self
    {
        $this->orderBy = $orderBy;

        return $this;
    }

    /**
     * Returns an array of column-level searches.
     */
    public function getSearchColumns(): array
    {
        return $this->searchColumns;
    }

    /**
     * @return $this
     */
    public function setColumnSearch(AbstractColumn $column, string $search, bool $isRegex = false): self
    {
        $this->searchColumns[$column->getName()] = ['column' => $column, 'search' => $search, 'regex' => $isRegex];

        return $this;
    }

    /**
     * @return string
     */
    public function getExporterName()
    {
        return $this->exporterName;
    }
}
