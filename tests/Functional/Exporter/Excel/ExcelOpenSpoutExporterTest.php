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

class ExcelOpenSpoutExporterTest extends WebTestCase
{
    /** @var KernelBrowser */
    private $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = self::createClient();
    }

    public function testExport(): void
    {
        $this->client->request('POST', '/exporter', ['_dt' => 'dt', '_exporter' => 'excel-openspout']);

        $response = $this->client->getResponse();

        // Using PhpSpreadsheet for tests
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

        // Test exporter options

        // - First column should be italic
        static::assertTrue($sheet->getCell('A2')->getAppliedStyle()->getFont()->getItalic());
        static::assertFalse($sheet->getCell('A2')->getAppliedStyle()->getFont()->getBold());
        // - Second column should be bold
        static::assertFalse($sheet->getCell('B2')->getAppliedStyle()->getFont()->getItalic());
        static::assertTrue($sheet->getCell('B2')->getAppliedStyle()->getFont()->getBold());
        // - First column should have a width of 20
        static::assertSame(20.0, $sheet->getColumnDimension('A')->getWidth());
        // - Second column should have a width of 30
        static::assertSame(30.0, $sheet->getColumnDimension('B')->getWidth());
    }

    public function testEmptyDataTable(): void
    {
        $this->client->request('POST', '/exporter-empty-datatable', ['_dt' => 'dt', '_exporter' => 'excel-openspout']);

        /** @var BinaryFileResponse $response */
        $response = $this->client->getResponse();

        static::assertTrue($response->isSuccessful());

        // Using PhpSpreadsheet for tests
        $sheet = IOFactory::load($response->getFile()->getPathname())->getActiveSheet();

        static::assertSame('dt.columns.firstName', $sheet->getCell('A1')->getFormattedValue());
        static::assertSame('dt.columns.lastName', $sheet->getCell('B1')->getFormattedValue());

        static::assertEmpty($sheet->getCell('A2')->getFormattedValue());
        static::assertEmpty($sheet->getCell('B2')->getFormattedValue());
    }

    public function testWithSearch(): void
    {
        $this->client->request('POST', '/exporter', [
            '_dt' => 'dt',
            '_exporter' => 'excel-openspout',
            'search' => ['value' => 'FirstName124'],
        ]);

        /** @var BinaryFileResponse $response */
        $response = $this->client->getResponse();

        // Using PhpSpreadsheet for tests
        $sheet = IOFactory::load($response->getFile()->getPathname())->getActiveSheet();

        static::assertSame('dt.columns.firstName', $sheet->getCell('A1')->getFormattedValue());
        static::assertSame('dt.columns.lastName', $sheet->getCell('B1')->getFormattedValue());

        static::assertSame('FirstName124', $sheet->getCell('A2')->getFormattedValue());
        static::assertSame('LastName124', $sheet->getCell('B2')->getFormattedValue());

        static::assertEmpty($sheet->getCell('A3')->getFormattedValue());
        static::assertEmpty($sheet->getCell('B3')->getFormattedValue());
    }

    public function testMaxCellLength(): void
    {
        $this->client->request('POST', '/exporter-long-text', [
            '_dt' => 'dt',
            '_exporter' => 'excel-openspout',
        ]);

        /** @var BinaryFileResponse $response */
        $response = $this->client->getResponse();

        $sheet = IOFactory::load($response->getFile()->getPathname())->getActiveSheet();

        // Value should be truncated to 32767 characters
        static::assertSame(str_repeat('a', 32767), $sheet->getCell('A2')->getFormattedValue());
    }

    public function testSpecialChars(): void
    {
        $this->client->request('POST', '/exporter-special-chars', [
            '_dt' => 'dt',
            '_exporter' => 'excel-openspout',
        ]);

        /** @var BinaryFileResponse $response */
        $response = $this->client->getResponse();

        $sheet = IOFactory::load($response->getFile()->getPathname())->getActiveSheet();

        // Value should not contain HTML encoded characters
        static::assertSame('<?xml version="1.0" encoding="UTF-8"?><hello>World</hello>', $sheet->getCell('A2')->getFormattedValue());
    }

    public function testWithTypes(): void
    {
        $this->client->request('POST', '/exporter-with-types', [
            '_dt' => 'dt',
            '_exporter' => 'excel-openspout',
        ]);

        /** @var BinaryFileResponse $response */
        $response = $this->client->getResponse();

        $sheet = IOFactory::load($response->getFile()->getPathname())->getActiveSheet();

        // Test columns
        static::assertEquals('stringValue', $sheet->getCell('A2')->getValue()->getPlainText());
        static::assertSame(1, $sheet->getCell('B2')->getValue());
        static::assertSame(1.1, $sheet->getCell('C2')->getValue());
        static::assertTrue($sheet->getCell('D2')->getValue());

        // Excel stores dates as a float where the integer part is the number of days since 1900-01-01 and the decimal part is the fraction of the day
        $expectedDateValue = (new \DateTimeImmutable('2021-01-01 00:00:00'))->diff(new \DateTimeImmutable('1900-01-01 00:00:00'))->days + 2;  // (Have to add 2 due to boundaries)
        static::assertSame($expectedDateValue, $sheet->getCell('E2')->getValue());
        static::assertSame(null, $sheet->getCell('F2')->getValue());
        static::assertSame('toStringValue', $sheet->getCell('G2')->getValue()->getPlainText());

        // This cell contains the exception message thrown when trying to cast an object without a __toString method to a string
        static::assertSame('Object of class class@anonymous could not be converted to string', $sheet->getCell('H2')->getValue()->getPlainText());
    }
}
