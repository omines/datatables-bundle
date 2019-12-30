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
    /** @var DataTableState */
    private $state;

    /** @var int|null */
    private $totalRows;

    /** @var int|null */
    private $filteredRows;

    /** @var string|null */
    private $identifierPropertyPath;

    /** @var array<string, mixed> */
    private $data;

    /**
     * AdapterQuery constructor.
     */
    public function __construct(DataTableState $state)
    {
        $this->state = $state;
    }

    public function getState(): DataTableState
    {
        return $this->state;
    }

    /**
     * @return int|null
     */
    public function getTotalRows()
    {
        return $this->totalRows;
    }

    /**
     * @param int|null $totalRows
     * @return $this
     */
    public function setTotalRows($totalRows): self
    {
        $this->totalRows = $totalRows;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getFilteredRows()
    {
        return $this->filteredRows;
    }

    /**
     * @param int|null $filteredRows
     * @return $this
     */
    public function setFilteredRows($filteredRows): self
    {
        $this->filteredRows = $filteredRows;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getIdentifierPropertyPath()
    {
        return $this->identifierPropertyPath;
    }

    /**
     * @param string|null $identifierPropertyPath
     * @return $this
     */
    public function setIdentifierPropertyPath($identifierPropertyPath): self
    {
        $this->identifierPropertyPath = $identifierPropertyPath;

        return $this;
    }

    /**
     * @param mixed $default
     * @return mixed|null
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * @param $value
     */
    public function set(string $key, $value)
    {
        $this->data[$key] = $value;
    }
}
