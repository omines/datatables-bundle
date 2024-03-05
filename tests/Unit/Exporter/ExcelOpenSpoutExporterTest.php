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

use Omines\DataTablesBundle\Exporter\DataTableExporterCollection;
use Omines\DataTablesBundle\Exporter\Excel\ExcelOpenSpoutExporter;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ExcelOpenSpoutExporterTest extends KernelTestCase
{
    /** @var DataTableExporterCollection */
    private $exporterCollection;

    protected function setUp(): void
    {
        $this->bootKernel();

        $this->exporterCollection = $this->getContainer()->get(DataTableExporterCollection::class);
    }

    public function testTag(): void
    {
        $this->assertInstanceOf(ExcelOpenSpoutExporter::class, $this->exporterCollection->getByName('excel-openspout'));
    }

    public function testName(): void
    {
        $this->assertSame('excel-openspout', $this->exporterCollection->getByName('excel-openspout')->getName());
    }
}
