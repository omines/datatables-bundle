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

    /** @var ?int */
    private $length = null;

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

    /** @var ?string */
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
     */
    public static function fromDefaults(DataTable $dataTable): self
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
    public function applyParameters(ParameterBag $parameters): void
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

    private function handleOrderBy(ParameterBag $parameters): void
    {
        if ($parameters->has('order')) {
            $this->orderBy = [];
            foreach ($parameters->all()['order'] ?? [] as $order) {
                $column = $this->getDataTable()->getColumn((int) $order['column']);
                $this->addOrderBy($column, $order['dir'] ?? DataTable::SORT_ASCENDING);
            }
        }
    }

    private function handleSearch(ParameterBag $parameters): void
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

    public function setStart(int $start): self
    {
        if ($start < 0) {
            @trigger_error(sprintf('Passing a negative value to the "%s::setStart()" method makes no logical sense, defaulting to 0 as the most sane default.', self::class), \E_USER_DEPRECATED);
            $start = 0;
        }

        $this->start = $start;

        return $this;
    }

    public function getLength(): ?int
    {
        return $this->length;
    }

    public function setLength(?int $length): self
    {
        if (is_integer($length) && $length < 1) {
            @trigger_error(sprintf('Calling the "%s::setLength()" method with a length less than 1 is deprecated since version 0.7 of this bundle. If you need to unrestrict the amount of records returned, pass null instead.', self::class), \E_USER_DEPRECATED);
            $length = null;
        }

        $this->length = $length;

        return $this;
    }

    public function getGlobalSearch(): string
    {
        return $this->globalSearch;
    }

    public function setGlobalSearch(string $globalSearch): self
    {
        $this->globalSearch = $globalSearch;

        return $this;
    }

    public function addOrderBy(AbstractColumn $column, string $direction = DataTable::SORT_ASCENDING): self
    {
        $this->orderBy[] = [$column, $direction];

        return $this;
    }

    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

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

    public function setColumnSearch(AbstractColumn $column, string $search, bool $isRegex = false): self
    {
        $this->searchColumns[$column->getName()] = ['column' => $column, 'search' => $search, 'regex' => $isRegex];

        return $this;
    }

    public function getExporterName(): ?string
    {
        return $this->exporterName;
    }
}
