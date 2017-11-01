<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\HttpFoundation\Response;
use Tests\Fixtures\AppKernel;

/**
 * FunctionalTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class FunctionalTest extends WebTestCase
{
    public function testFrontend()
    {
        $client = self::createClient();
        $crawler = $client->request('GET', '/');
        $this->assertSuccessful($response = $client->getResponse());

        // Verify HTML and JS were correctly inserted
        $this->assertSame(1, $crawler->filter('script:contains("var callbacks")')->count(), 'the Javascript is correctly inserted');
        $this->assertSame(2, $crawler->filter('table#datatable thead th')->count(), 'the HTML is correctly generated');
    }

    public function testPlainDataTable()
    {
        $client = self::createClient();
        $client->request('GET', '/plain');
        $this->assertSuccessful($response = $client->getResponse());

        $this->assertContains('application/json', $response->headers->get('Content-type'));

        echo $response->getContent();
        $json = json_decode($response->getContent());
        $this->assertSame(0, $json->draw);
        $this->assertSame(0, $json->recordsTotal);
        $this->assertSame(0, $json->recordsFiltered);
        $this->assertEmpty($json->data);
    }

    private function assertSuccessful(Response $response)
    {
        if (!$response->isSuccessful()) {
            echo sprintf("---- Response failed with %d ----\n%s\n------------------\n", $response->getStatusCode(), $response->getContent());
            $this->fail(sprintf('Response failed with HTTP status code %d', $response->getStatusCode()));
        }
    }

    protected static function getKernelClass()
    {
        return AppKernel::class;
    }
}
