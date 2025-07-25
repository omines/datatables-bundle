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
 * @phpstan-type SearchColumn array{column: AbstractColumn, search: string, regex: bool}
 * @phpstan-type OrderColumn array{AbstractColumn, string}
 */
final class DataTableState
{
    private DataTable $dataTable;

    private int $draw = 0;
    private int $start = 0;
    private ?int $length = null;
    private string $globalSearch = '';

    /** @var SearchColumn[] */
    private array $searchColumns = [];

    /** @var OrderColumn[] */
    private array $orderBy = [];

    private bool $isInitial = false;
    private bool $isCallback = false;
    private ?string $exporterName;

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
    public static function fromDefaults(DataTable $dataTable): static
    {
        $state = new static($dataTable);
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

        // DataTables insists on using -1 for infinity
        if ($this->length < 1) {
            $this->length = null;
        }

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
                try {
                    $column = $this->getDataTable()->getColumn((int) $order['column']);
                    $this->addOrderBy($column, $order['dir'] ?? DataTable::SORT_ASCENDING);
                } catch (\Throwable $t) {
                    // Column index and direction can be corrupted by malicious clients, ignore any exceptions thus caused
                }
            }
        }
    }

    private function handleSearch(ParameterBag $parameters): void
    {
        foreach ($parameters->all()['columns'] ?? [] as $key => $search) {
            $column = $this->dataTable->getColumn((int) $key);
            $value = $this->isInitial ? $search : $search['search']['value'] ?? '';

            // We do not check for $column->isSearchable() here, because at this point the
            // field option may not have been set yet. This makes the check for isSearchable()
            // unreliable.
            if ('' !== mb_trim($value)) {
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

    public function setStart(int $start): static
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

    public function setLength(?int $length): static
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

    public function setGlobalSearch(string $globalSearch): static
    {
        $this->globalSearch = $globalSearch;

        return $this;
    }

    public function addOrderBy(AbstractColumn $column, string $direction = DataTable::SORT_ASCENDING): static
    {
        $direction = mb_strtolower($direction);
        if (!in_array($direction, DataTable::SORT_OPTIONS, true)) {
            throw new \InvalidArgumentException(sprintf('Sort direction must be one of %s', implode(', ', DataTable::SORT_OPTIONS)));
        }
        $this->orderBy[] = [$column, $direction];

        return $this;
    }

    /**
     * @return OrderColumn[]
     */
    public function getOrderBy(): array
    {
        return $this->orderBy;
    }

    /**
     * @param OrderColumn[] $orderBy
     */
    public function setOrderBy(array $orderBy = []): static
    {
        $this->orderBy = [];
        foreach ($orderBy as [$column, $direction]) {
            $this->addOrderBy($column, $direction);
        }

        return $this;
    }

    /**
     * Returns an array of column-level searches.
     *
     * @param bool $onlySearchable if true, only returns columns for which isSearchable() is true
     * @return SearchColumn[]
     */
    public function getSearchColumns(bool $onlySearchable = true): array
    {
        // `searchColumns` may include columns that are not searchable, so we filter them out here.
        return array_filter($this->searchColumns, fn ($searchInfo) => !$onlySearchable || $searchInfo['column']->isSearchable());
    }

    public function setColumnSearch(AbstractColumn $column, string $search, bool $isRegex = false): static
    {
        $this->searchColumns[$column->getName()] = ['column' => $column, 'search' => $search, 'regex' => $isRegex];

        return $this;
    }

    public function getExporterName(): ?string
    {
        return $this->exporterName;
    }

    public function isExport(): bool
    {
        return null !== $this->exporterName;
    }
}
