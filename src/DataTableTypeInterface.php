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

/**
 * DataTableTypeInterface.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
interface DataTableTypeInterface
{
    public function configure(DataTable $dataTable, array $options);
}
