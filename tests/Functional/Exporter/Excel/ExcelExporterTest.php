<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Functional\Exporter\Excel;

use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class ExcelExporterTest extends WebTestCase
{
    /** @var KernelBrowser */
    private $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = self::createClient();
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function testExport(): void
    {
        $this->client->request('POST', '/exporter', ['_dt' => 'dt', '_exporter' => 'excel']);

        /** @var BinaryFileResponse $response */
        $response = $this->client->getResponse();

        $sheet = IOFactory::load($response->getFile()->getPathname())->getActiveSheet();

        static::assertSame('dt.columns.firstName', $sheet->getCell('A1')->getFormattedValue());
        static::assertSame('dt.columns.lastName', $sheet->getCell('B1')->getFormattedValue());

        static::assertSame('FirstName0', $sheet->getCell('A2')->getFormattedValue());
        static::assertSame('LastName0', $sheet->getCell('B2')->getFormattedValue());

        static::assertSame('FirstName1', $sheet->getCell('A3')->getFormattedValue());
        static::assertSame('LastName1', $sheet->getCell('B3')->getFormattedValue());

        static::assertSame('FirstName2', $sheet->getCell('A4')->getFormattedValue());
        static::assertSame('LastName2', $sheet->getCell('B4')->getFormattedValue());

        static::assertSame('FirstName3', $sheet->getCell('A5')->getFormattedValue());
        static::assertSame('LastName3', $sheet->getCell('B5')->getFormattedValue());

        static::assertSame('FirstName4', $sheet->getCell('A6')->getFormattedValue());
        static::assertSame('LastName4', $sheet->getCell('B6')->getFormattedValue());
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function testEmptyDataTable(): void
    {
        $this->client->request('POST', '/exporter-empty-datatable', ['_dt' => 'dt', '_exporter' => 'excel']);

        /** @var BinaryFileResponse $response */
        $response = $this->client->getResponse();

        static::assertTrue($response->isSuccessful());

        $sheet = IOFactory::load($response->getFile()->getPathname())->getActiveSheet();

        static::assertSame('dt.columns.firstName', $sheet->getCell('A1')->getFormattedValue());
        static::assertSame('dt.columns.lastName', $sheet->getCell('B1')->getFormattedValue());

        static::assertEmpty($sheet->getCell('A2')->getFormattedValue());
        static::assertEmpty($sheet->getCell('B2')->getFormattedValue());
    }

    /**
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Reader\Exception
     */
    public function testWithSearch(): void
    {
        $this->client->request('POST', '/exporter', [
            '_dt' => 'dt',
            '_exporter' => 'excel',
            'search' => ['value' => 'FirstName124'],
        ]);

        /** @var BinaryFileResponse $response */
        $response = $this->client->getResponse();

        $sheet = IOFactory::load($response->getFile()->getPathname())->getActiveSheet();

        static::assertSame('dt.columns.firstName', $sheet->getCell('A1')->getFormattedValue());
        static::assertSame('dt.columns.lastName', $sheet->getCell('B1')->getFormattedValue());

        static::assertSame('FirstName124', $sheet->getCell('A2')->getFormattedValue());
        static::assertSame('LastName124', $sheet->getCell('B2')->getFormattedValue());

        static::assertEmpty($sheet->getCell('A3')->getFormattedValue());
        static::assertEmpty($sheet->getCell('B3')->getFormattedValue());
    }
}
