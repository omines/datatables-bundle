<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Unit\Exporter;

use Omines\DataTablesBundle\Exception\InvalidArgumentException;
use Omines\DataTablesBundle\Exporter\DataTableExporterCollection;
use Omines\DataTablesBundle\Exporter\DataTableExporterManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\DataCollectorTranslator;
use Symfony\Component\Translation\TranslatorInterface as LegacyTranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * DataTableExporterManagerTest.
 *
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class DataTableExporterManagerTest extends TestCase
{
    public function testTranslatorInjection()
    {
        $exporterCollectionMock = $this->createMock(DataTableExporterCollection::class);

        static::expectException(InvalidArgumentException::class);
        (new DataTableExporterManager($exporterCollectionMock, null));

        static::expectException(InvalidArgumentException::class);
        (new DataTableExporterManager($exporterCollectionMock, $this->createMock(DataCollectorTranslator::class)));

        static::assertInstanceOf(DataTableExporterManager::class, (new DataTableExporterManager($exporterCollectionMock, $this->createMock(TranslatorInterface::class))));
        static::assertInstanceOf(DataTableExporterManager::class, (new DataTableExporterManager($exporterCollectionMock, $this->createMock(LegacyTranslatorInterface::class))));
    }
}
