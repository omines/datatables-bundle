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

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class DataTableExporterResponseEventTest extends WebTestCase
{
    /** @var KernelBrowser */
    private $client;

    protected function setUp(): void
    {
        self::ensureKernelShutdown();
        $this->client = self::createClient();
    }

    /**
     * @dataProvider exporterNameProvider
     */
    public function testPreResponseEvent(string $exporterName, string $ext): void
    {
        $this->client->request('POST', '/exporter', ['_dt' => 'dt', '_exporter' => $exporterName]);

        /** @var BinaryFileResponse $response */
        $response = $this->client->getResponse();

        static::assertStringContainsString($response->headers->get('content-disposition'), sprintf('attachment; filename=custom_filename.%s', $ext));
    }

    public function exporterNameProvider(): array
    {
        return [
            ['excel', 'xlsx'],
            ['txt', 'txt'],
        ];
    }
}
