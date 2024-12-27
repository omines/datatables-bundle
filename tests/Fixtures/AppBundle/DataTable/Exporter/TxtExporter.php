<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Fixtures\AppBundle\DataTable\Exporter;

use Omines\DataTablesBundle\Exporter\AbstractDataTableExporter;

/**
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class TxtExporter extends AbstractDataTableExporter
{
    #[\Override]
    public function export(array $columnNames, \Iterator $data, array $columnOptions): \SplFileInfo
    {
        $filename = sys_get_temp_dir() . '/dt.txt';
        $handle = fopen($filename, 'w');

        fwrite($handle, implode(' ', $columnNames) . "\n");

        foreach ($data as $datum) {
            fwrite($handle, implode(' ', $datum) . "\n");
        }

        fclose($handle);

        return new \SplFileInfo($filename);
    }

    public function getMimeType(): string
    {
        return 'text/plain';
    }

    public function getName(): string
    {
        return 'txt';
    }
}
