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

/**
 * FunctionalTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class FunctionalTest extends WebTestCase
{
    public function testTest()
    {
        $client = self::createClient();
        $client->request('GET', '/');
        $response = $client->getResponse();

        $this->assertSuccessful($response);
    }

    private function assertSuccessful(Response $response)
    {
        if (!$response->isSuccessful()) {
            echo sprintf("---- Response failed with %d ----\n%s\n------------------\n", $response->getStatusCode(), $response->getContent());
            $this->fail(sprintf('Response failed with HTTP status code %d', $response->getStatusCode()));
        }
    }
}
