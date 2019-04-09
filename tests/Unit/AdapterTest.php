<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Unit;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Symfony\Bundle\FrameworkBundle\Tests\TestCase;

/**
 * AdapterTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class AdapterTest extends TestCase
{
    /**
     * @expectedException \Omines\DataTablesBundle\Exception\InvalidConfigurationException
     * @expectedExceptionMessage Doctrine has no manager for entity "foobar"
     */
    public function testInvalidEntity()
    {
        /** @var Registry $registryMock */
        $registryMock = $this->createMock(Registry::class);
        $adapter = new ORMAdapter($registryMock);
        $adapter->configure([
            'entity' => 'foobar',
        ]);
    }
}
