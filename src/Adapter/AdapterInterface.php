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
 * AdapterInterface.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
interface AdapterInterface
{
    /**
     * Provides initial configuration to the adapter.
     *
     * @param array<string, mixed> $options Adapter specific options array
     */
    public function configure(array $options): void;

    /**
     * Processes a datatable's state into a result set fit for further processing.
     */
    public function getData(DataTableState $state): ResultSetInterface;
}
