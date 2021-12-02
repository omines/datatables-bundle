<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Functional\Exporter\Csv;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * CsvExporterTest.
 *
 * @author Maxime Pinot <maxime.pinot@gbh.fr>
 */
class CsvExporterTest extends WebTestCase
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
        $this->client->request('POST', '/exporter', ['_dt' => 'dt', '_exporter' => 'csv']);

        /** @var BinaryFileResponse $response */
        $response = $this->client->getResponse();

        $csvFile = fopen($response->getFile()->getPathname(), 'r');

        self::assertEquals(['dt.columns.firstName', 'dt.columns.lastName'], fgetcsv($csvFile));

        $i = 0;
        while (false !== ($row = fgetcsv($csvFile))) {
            self::assertEquals(['FirstName' . $i, 'LastName' . $i], $row);
            ++$i;
        }
    }
}
