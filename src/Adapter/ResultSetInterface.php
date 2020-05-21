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
 * ResultSetInterface.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
interface ResultSetInterface
{
    /**
     * Retrieves the total number of accessible records in the original data.
     */
    public function getTotalRecords(): int;

    /**
     * Retrieves the number of records available after applying filters.
     */
    public function getTotalDisplayRecords(): int;

    /**
     * Returns the raw data in the result set.
     */
    public function getData(): \Iterator;
}
