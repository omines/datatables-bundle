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

use Omines\DataTablesBundle\Exporter\AbstractDataTableExporter;
use OpenSpout\Common\Entity\Cell;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\AutoFilter;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Excel exporter using OpenSpout.
 */
class ExcelOpenSpoutExporter extends AbstractDataTableExporter
{
    /**
     * This is an Excel limitation. See: https://support.microsoft.com/en-us/office/excel-specifications-and-limits-1672b34d-7043-467e-8e27-269d656771c3.
     */
    public const MAX_CHARACTERS_PER_CELL = 32767;

    #[\Override]
    public function export(array $columnNames, \Iterator $data, array $columnOptions): \SplFileInfo
    {
        $filePath = sys_get_temp_dir() . '/' . uniqid('dt') . '.xlsx';

        $writer = new Writer();
        $writer->openToFile($filePath);

        // Header
        $writer->addRow(Row::fromValues($columnNames, (new Style())->setFontBold()));

        $truncated = false;
        $rowCount = 0;

        foreach ($data as $rowValues) {
            reset($columnOptions);
            $row = new Row([]);
            foreach ($rowValues as $value) {
                $options = current($columnOptions);
                if (false === $options) {
                    throw new \LogicException('Mismatch in number of row values and number of column options');  // (This prevents PHPStan complaining)
                }

                if (is_string($value)) {
                    // We strip HTML tags and unescape the value by default, because
                    // TextColumn::normalize() will encode HTML special characters (unless `raw` is set).
                    if ($options['stripTags']) {
                        $value = htmlspecialchars_decode(strip_tags($value), ENT_QUOTES | ENT_SUBSTITUTE);
                    }

                    // Excel has a limit of 32,767 characters per cell
                    if (mb_strlen($value) > static::MAX_CHARACTERS_PER_CELL) {
                        $truncated = true;
                        $value = mb_substr($value, 0, static::MAX_CHARACTERS_PER_CELL);
                    }
                }

                // Do not wrap text
                $style = $this->resolveStyleOption($options['style'], $value);
                $row->addCell($this->normalizeToCell($value, $style));

                next($columnOptions);
            }
            $writer->addRow($row);
            ++$rowCount;
        }

        // Sheet configuration (AutoFilter, freeze row, better column width)
        $sheet = $writer->getCurrentSheet();
        $sheet->setAutoFilter(new AutoFilter(0, 1,
            max(count($columnNames) - 1, 0), $rowCount + 1));
        $sheet->setSheetView((new SheetView())->setFreezeRow(2));

        // Column widths
        foreach ($columnOptions as $index => $options) {
            $sheet->setColumnWidth($options['columnWidth'], $index + 1);
        }

        if ($truncated) {
            // Add a notice to the sheet if there is truncated data.
            //
            // TODO: when the user opens the XLSX, it will open at the first sheet, not at this notice sheet.
            //  Thus the user won't see the notice immediately.
            //  This needs to have a better solution.
            $writer
                ->addNewSheetAndMakeItCurrent()
                ->setName('Notice');
            $writer->addRow(Row::fromValues(['Some cell values were too long! They were truncated to fit the 32,767 character limit.'], (new Style())->setFontBold()));
        }

        $writer->close();

        return new \SplFileInfo($filePath);
    }

    private function resolveStyleOption(Style|callable $style, mixed $value): Style
    {
        return $style instanceof Style ? $style : $style($value);
    }

    private function normalizeToCell(mixed $value, ?Style $style = null): Cell
    {
        if (
            is_scalar($value)  // (bool, int, float, string)
            || null === $value
            || $value instanceof \DateTimeInterface
            || $value instanceof \DateInterval
        ) {
            return Cell::fromValue($value, $style);
        } else {
            // Try casting to string, else put an error message in the cell
            try {
                return Cell::fromValue((string) $value, $style);
            } catch (\Throwable $e) {
                return Cell::fromValue($e->getMessage(), (new Style())->setFontItalic());
            }
        }
    }

    public function getMimeType(): string
    {
        return 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    }

    public function getName(): string
    {
        return 'excel-openspout';
    }

    #[\Override]
    public function configureColumnOptions(OptionsResolver $resolver): void
    {
        $resolver
            ->setDefaults([
                'style' => (new Style())->setShouldWrapText(false),
                'columnWidth' => 24,
                'stripTags' => true,
            ])
            ->setAllowedTypes('style', [Style::class, 'callable'])
            ->setAllowedTypes('columnWidth', ['int', 'float'])
            ->setAllowedTypes('stripTags', 'bool')
            ->setInfo('stripTags', 'When true, will strip HTML tags and unescape special characters from string data.')
        ;
    }

    #[\Override]
    public function supportsRawData(): bool
    {
        return true;
    }
}
