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
 */
class ArrayResultSet implements ResultSetInterface
{
    /** @var array */
    private $data;

    /** @var int */
    private $totalRows;

    /** @var int */
    private $totalFilteredRows;

    /**
     * ArrayResultSet constructor.
     */
    public function __construct(array $data, int $totalRows = null, int $totalFilteredRows = null)
    {
        $this->data = $data;
        $this->totalRows = $totalRows ?? count($data);
        $this->totalFilteredRows = $totalFilteredRows ?? $this->totalRows;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalRecords(): int
    {
        return $this->totalRows;
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalDisplayRecords(): int
    {
        return $this->totalFilteredRows;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(): \Iterator
    {
        return new \ArrayIterator($this->data);
    }
}
