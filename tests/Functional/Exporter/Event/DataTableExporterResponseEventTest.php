<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Functional\Exporter\Event;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class DataTableExporterResponseEventTest extends WebTestCase
{
    /** @var Client */
    private $client;

    protected function setUp()
    {
        $this->client = static::createClient();
    }

    /**
     * @param string $exporterName
     * @param string $ext
     *
     * @dataProvider exporterNameProvider
     */
    public function testPreResponseEvent(string $exporterName, string $ext)
    {
        $this->client->request('POST', '/exporter', ['_dt' => 'dt', '_exporter' => $exporterName]);

        /** @var BinaryFileResponse $response */
        $response = $this->client->getResponse();

        $headers = [
            sprintf('attachment; filename="custom_filename.%s"', $ext), // Symfony 3
            sprintf('attachment; filename=custom_filename.%s', $ext),    // Symfony 4
        ];

        static::assertContains($response->headers->get('content-disposition'), $headers);
    }

    public function exporterNameProvider()
    {
        return [
            ['excel', 'xlsx'],
            ['txt', 'txt'],
        ];
    }

    protected function tearDown()
    {
        $this->client = null;
    }
}
