<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Exporter\Csv;

use Omines\DataTablesBundle\Exporter\DataTableExporterInterface;

/**
 * Exports DataTable data to a CSV file.
 *
 * @author Maxime Pinot <maxime.pinot@gbh.fr>
 */
class CsvExporter implements DataTableExporterInterface
{
    public function export(array $columnNames, \Iterator $data): \SplFileInfo
    {
        $filePath = sys_get_temp_dir() . '/' . uniqid('dt') . '.csv';

        if (false === ($file = fopen($filePath, 'w'))) {
            throw new \RuntimeException('Failed to create temporary file at ' . $filePath);
        }

        fputcsv($file, $columnNames);

        foreach ($data as $row) {
            fputcsv($file, array_map('strip_tags', $row));
        }

        fclose($file);

        return new \SplFileInfo($filePath);
    }

    public function getMimeType(): string
    {
        return 'text/csv';
    }

    public function getName(): string
    {
        return 'csv';
    }
}
