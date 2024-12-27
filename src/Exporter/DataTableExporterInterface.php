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
     *
     * @param mixed[] $columnNames
     */
    public function export(array $columnNames, \Iterator $data): \SplFileInfo;

    /**
     * The MIME type of the exported file.
     */
    public function getMimeType(): string;

    /**
     * A unique name to identify the exporter.
     */
    public function getName(): string;

    /**
     * Returns whether the exporter supports non-string data.
     *
     * The exporter should convert input types to the appropriate output types (e.g. an
     * int becomes a number type in Excel). Non-supported types should be cast to a
     * string.
     *
     * When this is true, `AbstractColumn::normalize()` and `AbstractColumn::render()`
     * will not be called.
     */
    public function supportsRawData(): bool;
}
