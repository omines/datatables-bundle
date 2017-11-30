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
 * DataTableRendererInterface.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
interface DataTableRendererInterface
{
    /**
     * Provides the HTML layout of the configured datatable.
     *
     * @param DataTable $dataTable
     * @param string $template
     * @param array $parameters
     * @return string
     */
    public function renderDataTable(DataTable $dataTable, string $template, array $parameters): string;
}
