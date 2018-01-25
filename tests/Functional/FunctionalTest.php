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
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\ExceptionDataCollector;
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

        $content = $response->getContent();
        $this->assertContains('"name":"dt"', $content);
        $this->assertContains('(filtered from _MAX_ total entries)', $content);
        $json = $this->callDataTableUrl('/?_dt=noCDN&_init=true');
        $this->assertEmpty($json->data);
    }

    public function testPlainDataTable()
    {
        $json = $this->callDataTableUrl('/plain?_dt=persons&_init=true&draw=1&start=25&length=50&order[0][column]=0&order[0][dir]=desc');

        $this->assertSame(1, $json->draw);
        $this->assertSame(125, $json->recordsTotal);
        $this->assertSame(125, $json->recordsFiltered);
        $this->assertCount(50, $json->data);

        $this->assertContains('<table id="persons"', $json->template);
        $this->assertNotEmpty($json->options);

        $sample = $json->data[5];
        $this->assertSame('FirstName94', $sample->firstName);
        $this->assertSame('LastName94', $sample->lastName);
        $this->assertEmpty($sample->employedSince);
        $this->assertSame('FirstName94 &lt;img src=&quot;https://symfony.com/images/v5/logos/sf-positive.svg&quot;&gt; LastName94', $sample->fullName);
        $this->assertRegExp('#href="/employee/[0-9]+"#', $sample->buttons);

        $this->assertSame('04-07-2016', $json->data[6]->employedSince);
    }

    public function testTypeDataTable()
    {
        $json = $this->callDataTableUrl('/type?_dt=persons');

        $this->assertSame(0, $json->draw);
        $this->assertSame(6, $json->recordsTotal);
        $this->assertSame('Donald', $json->data[0]->firstName);
        $this->assertSame('TRUMP', $json->data[0]->lastName);
        $this->assertSame('01-01-2017', $json->data[0]->lastActivity);

        $json = $this->callDataTableUrl('/type?_dt=persons&draw=1&search[value]=Bush');

        $this->assertSame(2, $json->recordsFiltered);
    }

    public function testServiceDataTable()
    {
        $json = $this->callDataTableUrl('/service?_dt=persons&draw=2');

        $this->assertSame(2, $json->draw);
        $this->assertStringStartsWith('Company ', $json->data[0]->company);
        $this->assertSame('LastName0 (Company 0)', $json->data[0]->fullName);

        $json = $this->callDataTableUrl('/service?_dt=persons&draw=2&order[0][column]=2&order[0][dir]=desc&search[value]=24&columns[1][search][value]=24');

        $this->assertCount(2, $json->data);
        $this->assertStringStartsWith('Company ', $json->data[0]->company);
        $this->assertSame('LastName24 (Company 4)', $json->data[0]->fullName);
    }

    public function testCustomDataTable()
    {
        $json = $this->callDataTableUrl('/custom?_dt=dt&draw=2');

        $this->assertSame(2, $json->draw);
        $this->assertStringStartsWith('Company ', $json->data[0]->company);
    }

    public function testGroupedDataTable()
    {
        $this->markTestSkipped('Group by functionality is currently not working correctly');

        $json = $this->callDataTableUrl('/grouped?_dt=companies');

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
                $content = '';

                /** @var ExceptionDataCollector $collector */
                $collector = $profile->getCollector('exception');

                /** @var FlattenException $exception */
                $exception = $collector->getException();

                foreach ($exception->toArray() as $exception) {
                    $content .= "{$exception['class']}: {$exception['message']}\n";
                    foreach ($exception['trace'] as $trace) {
                        $content .= "    {$trace['file']}:{$trace['line']}\n";
                    }
                }
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
