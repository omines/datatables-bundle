<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Adapter;

use Omines\DataTablesBundle\DataTableState;

/**
 * AdapterQuery encapsulates a single request to an adapter, used by the AbstractAdapter base class. It is generically
 * used as a context container allowing temporary data to be stored.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class AdapterQuery
{
    private ?int $totalRows;

    private ?int $filteredRows;

    private ?string $identifierPropertyPath = null;

    /** @var array<string, mixed> */
    private array $data;

    /**
     * AdapterQuery constructor.
     */
    public function __construct(private DataTableState $state)
    {}

    public function getState(): DataTableState
    {
        return $this->state;
    }

    public function getTotalRows(): ?int
    {
        return $this->totalRows;
    }

    public function setTotalRows(?int $totalRows): self
    {
        $this->totalRows = $totalRows;

        return $this;
    }

    public function getFilteredRows(): ?int
    {
        return $this->filteredRows;
    }

    public function setFilteredRows(?int $filteredRows): self
    {
        $this->filteredRows = $filteredRows;

        return $this;
    }

    public function getIdentifierPropertyPath(): ?string
    {
        return $this->identifierPropertyPath;
    }

    public function setIdentifierPropertyPath(?string $identifierPropertyPath): self
    {
        $this->identifierPropertyPath = $identifierPropertyPath;

        return $this;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->data[$key] = $value;
    }
}
