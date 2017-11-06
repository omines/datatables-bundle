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

use Symfony\Bundle\FrameworkBundle\Client;
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
    /** @var Client */
    private $client;

    protected function setUp()
    {
        $this->client = self::createClient();
    }

    public function testFrontend()
    {
        $this->client->enableProfiler();
        $crawler = $this->client->request('GET', '/');
        $this->assertSuccessful($response = $this->client->getResponse());

        // Verify HTML and JS were correctly inserted
        $this->assertSame(2, $crawler->filter('script:contains("var callbacks")')->count(), 'the Javascript is correctly inserted');
        $this->assertSame(4, $crawler->filter('table thead th')->count(), 'the HTML is correctly generated');
    }

    public function testPlainDataTable()
    {
        $this->client->enableProfiler();
        $json = $this->callDataTableUrl('/plain');

        $this->assertSame(0, $json->draw);
        $this->assertSame(125, $json->recordsTotal);
        $this->assertSame(125, $json->recordsFiltered);
        $this->assertCount(125, $json->data);

        $sample = $json->data[5];
        $this->assertSame('FirstName5', $sample->firstName);
        $this->assertSame('LastName5', $sample->lastName);
        $this->assertSame('FirstName5 &lt;img src=&quot;https://symfony.com/images/v5/logos/sf-positive.svg&quot;&gt; LastName5', $sample->fullName);
        $this->assertContains('<button', $sample->buttons);
    }

    public function testTypeDataTable()
    {
        $json = $this->callDataTableUrl('/type');

        $this->assertSame(0, $json->draw);
        $this->assertSame(6, $json->recordsTotal);
        $this->assertSame('Donald', $json->data[0]->firstName);
    }

    public function testServiceDataTable()
    {
        $json = $this->callDataTableUrl('/service?draw=2');

        $this->assertSame(2, $json->draw);
        $this->assertStringStartsWith('Company ', $json->data[0]->company);
    }

    private function callDataTableUrl(string $url)
    {
        $this->client->enableProfiler();
        $this->client->request('GET', $url);
        $this->assertSuccessful($response = $this->client->getResponse());
        $this->assertContains('application/json', $response->headers->get('Content-type'));

        return json_decode($response->getContent());
    }

    private function assertSuccessful(Response $response)
    {
        if (!$response->isSuccessful()) {
            if ($profile = $this->client->getProfile()) {
                $content = print_r($profile->getCollector('exception')->getException()->toArray(), true);
            } else {
                $content = strip_tags($response->getContent());
            }

            echo sprintf("---- Response failed with %d ----\n%s\n------------------\n", $response->getStatusCode(), $content);
            $this->fail(sprintf('Response failed with HTTP status code %d', $response->getStatusCode()));
        }
    }

    protected static function getKernelClass()
    {
        return AppKernel::class;
    }
}
