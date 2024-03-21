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
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;

/**
 * Excel exporter using OpenSpout.
 */
class ExcelOpenSpoutExporter implements DataTableExporterInterface
{
    public function export(array $columnNames, \Iterator $data): \SplFileInfo
    {
        $filePath = sys_get_temp_dir() . '/' . uniqid('dt') . '.xlsx';

        // Header
        $rows = [Row::fromValues($columnNames, (new Style())->setFontBold())];

        // Data
        foreach ($data as $row) {
            // Remove HTML tags
            $values = array_map('strip_tags', $row);
            $rows[] = Row::fromValues($values);
        }

        // Write rows
        $writer = new Writer();
        $writer->openToFile($filePath);
        $writer->addRows($rows);

        // Sheet configuration (AutoFilter, freeze row, better column width)
        $sheet = $writer->getCurrentSheet();
        $sheet->setAutoFilter(new AutoFilter(0, 1,
            max(count($columnNames) - 1, 0), max(count($rows), 1)));
        $sheet->setSheetView((new SheetView())->setFreezeRow(2));
        $sheet->setColumnWidthForRange(24, 1, max(count($columnNames), 1));

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
