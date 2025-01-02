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
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 *
 * @phpstan-type Row array<(int|string), mixed>
 *
 * (Note: when using ArrayAdapter, the Row keys may be integers instead of strings.)
 */
class ResultSet implements ResultSetInterface
{
    /**
     * @param \Iterator<Row> $data
     */
    public function __construct(
        private readonly \Iterator $data,
        private readonly int $totalRows,
        private readonly int $totalFilteredRows,
    ) {
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
        return $this->data;
    }
}
