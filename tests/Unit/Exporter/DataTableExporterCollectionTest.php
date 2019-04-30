<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Unit\Exporter;

use Omines\DataTablesBundle\Exception\UnknownDataTableExporterException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * DataTableExporterCollectionTest.
 *
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class DataTableExporterCollectionTest extends KernelTestCase
{
    protected function setUp()
    {
        static::bootKernel();
    }

    public function testUnknownExporter()
    {
        static::expectException(UnknownDataTableExporterException::class);
        static::$kernel
            ->getContainer()
            ->get('test.Omines\DataTablesBundle\Exporter\DataTableExporterCollection')
            ->getByName('unknown-exporter');
    }
}
