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

/**
 * ArrayResultSet.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 *
 * @phpstan-type Row array<string, mixed>
 */
class ArrayResultSet implements ResultSetInterface
{
    /** @var Row[] */
    private array $data;
    private int $totalRows;
    private int $totalFilteredRows;

    /**
     * @param Row[] $data
     */
    public function __construct(array $data, int $totalRows = null, int $totalFilteredRows = null)
    {
        $this->data = $data;
        $this->totalRows = $totalRows ?? count($data);
        $this->totalFilteredRows = $totalFilteredRows ?? $this->totalRows;
    }

    public function getTotalRecords(): int
    {
        return $this->totalRows;
    }

    public function getTotalDisplayRecords(): int
    {
        return $this->totalFilteredRows;
    }

    public function getData(): \Iterator
    {
        return new \ArrayIterator($this->data);
    }
}
