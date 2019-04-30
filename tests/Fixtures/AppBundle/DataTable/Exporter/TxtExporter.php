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

use Omines\DataTablesBundle\Exporter\DataTableExporterInterface;

/**
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class TxtExporter implements DataTableExporterInterface
{
    /**
     * {@inheritdoc}
     */
    public function export(array $columnNames, \Iterator $data): \SplFileInfo
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

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'txt';
    }
}
