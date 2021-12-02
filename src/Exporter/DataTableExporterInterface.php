<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Exporter;

/**
 * Defines a DataTable exporter.
 *
 * @author Maxime Pinot <contact@maximepinot.com>
 */
interface DataTableExporterInterface
{
    /**
     * Exports the data from the DataTable to a file.
     */
    public function export(array $columnNames, \Iterator $data): \SplFileInfo;

    /**
     * A unique name to identify the exporter.
     */
    public function getName(): string;
}
