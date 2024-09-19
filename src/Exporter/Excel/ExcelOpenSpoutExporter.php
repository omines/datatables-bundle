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
use OpenSpout\Common\Entity\Cell;
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

        // Style definitions
        $noWrapTextStyle = (new Style())->setShouldWrapText(false);
        $boldStyle = (new Style())->setFontBold();

        $writer = new Writer();
        $writer->openToFile($filePath);

        // Add header
        $writer->addRow(Row::fromValues($columnNames, $boldStyle));

        $truncated = false;
        $maxCharactersPerCell = 32767;  // E.g. https://support.microsoft.com/en-us/office/excel-specifications-and-limits-1672b34d-7043-467e-8e27-269d656771c3
        $rowCount = 0;

        foreach ($data as $rowValues) {
            $row = new Row([]);
            foreach ($rowValues as $value) {
                // I assume that $value is always a string

                // The data that we get may contain rich HTML. But OpenSpout does not support this.
                // We just strip all HTML tags and unescape the remaining text.
                $value = htmlspecialchars_decode(strip_tags($value), ENT_QUOTES | ENT_SUBSTITUTE);

                // Excel has a limit of 32,767 characters per cell
                if (mb_strlen($value) > $maxCharactersPerCell) {
                    $truncated = true;
                    $value = mb_substr($value, 0, $maxCharactersPerCell);
                }

                // Do not wrap text
                $row->addCell(Cell::fromValue($value, $noWrapTextStyle));
            }
            $writer->addRow($row);
            ++$rowCount;
        }

        // Sheet configuration (AutoFilter, freeze row, better column width)
        $sheet = $writer->getCurrentSheet();
        $sheet->setAutoFilter(new AutoFilter(0, 1,
            max(count($columnNames) - 1, 0), $rowCount + 1));
        $sheet->setSheetView((new SheetView())->setFreezeRow(2));
        $sheet->setColumnWidthForRange(24, 1, max(count($columnNames), 1));

        if ($truncated) {
            // Add a notice to the sheet if there is truncated data.
            //
            // TODO: when the user opens the XLSX, it will open at the first sheet, not at this notice sheet.
            //  Thus the user won't see the notice immediately.
            //  This needs to have a better solution.
            $writer
                ->addNewSheetAndMakeItCurrent()
                ->setName('Notice');
            $writer->addRow(Row::fromValues(['Some cell values were too long! They were truncated to fit the 32,767 character limit.'], $boldStyle));
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
