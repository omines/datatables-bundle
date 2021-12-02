<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Functional\Adapter\Doctrine;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Cache\DoctrineProvider;
use Tests\Fixtures\AppBundle\Controller\ORMAdapterEventsController;

/**
 * Tests events.
 *
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class ORMAdapterEventsTest extends WebTestCase
{
    public function testPreQueryEvent()
    {
        $client = self::createClient();

        /** @var DoctrineProvider $doctrineProvider */
        $doctrineProvider = self::$kernel->getContainer()->get('doctrine')->getManager()->getConfiguration()->getResultCacheImpl();
        $doctrineProvider->delete(ORMAdapterEventsController::PRE_QUERY_RESULT_CACHE_ID);

        $client->request('POST', '/orm-adapter-events/pre-query', ['_dt' => 'dt', '_init' => true]);

        static::assertTrue($doctrineProvider->contains(ORMAdapterEventsController::PRE_QUERY_RESULT_CACHE_ID));
    }
}
