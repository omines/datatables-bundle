<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Exporter\Excel;

use Omines\DataTablesBundle\Exporter\DataTableExporterInterface;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Excel exporter using OpenSpout.
 */
class ExcelOpenSpoutExporter implements DataTableExporterInterface
{
    public function export(array $columnNames, \Iterator $data): \SplFileInfo
    {
        $filePath = sys_get_temp_dir() . '/' . uniqid('dt') . '.xlsx';

        $writer = new Writer();
        $writer->openToFile($filePath);

        // Write header
        $boldStyle = (new Style())->setFontBold();
        $writer->addRow(Row::fromValues($columnNames, $boldStyle));

        // Write data
        foreach ($data as $row) {
            // Remove HTML tags
            $values = array_map('strip_tags', $row);

            $writer->addRow(Row::fromValues($values));
        }

        $writer->close();

        return new \SplFileInfo($filePath);
    }

    public function getMimeType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public function getName(): string
    {
        return 'excel-openspout';
    }
}
