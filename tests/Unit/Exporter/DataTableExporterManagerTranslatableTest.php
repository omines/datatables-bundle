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

use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\Exporter\DataTableExporterCollection;
use Omines\DataTablesBundle\Exporter\DataTableExporterInterface;
use Omines\DataTablesBundle\Exporter\DataTableExporterManager;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Translation\TranslatableMessage;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tests that the DataTableExporterManager supports TranslatableInterface labels.
 */
class DataTableExporterManagerTranslatableTest extends KernelTestCase
{
    public function testExportSupportsTranslatableColumnLabels(): void
    {
        $exporter = $this->createMock(DataTableExporterInterface::class);
        $exporterCollection = $this->createStub(DataTableExporterCollection::class);
        $exporterCollection
            ->method('getByName')
            ->willReturn($exporter)
        ;

        // Assert that the column names given to DataTableExporterInterface::export() are
        // 'translated' from a TranslatableMessage to a string.
        $exporter->expects(self::once())
            ->method('export')
            ->with(['invalid-translation-key'])
        ;

        $manager = new DataTableExporterManager($exporterCollection, $this->getContainer()->get(TranslatorInterface::class));
        $table = new DataTable($this->createStub(EventDispatcherInterface::class), $manager);
        $table
            ->add('name', TextColumn::class, [
                'label' => new TranslatableMessage('invalid-translation-key'),
            ])
            ->setAdapter(new ArrayAdapter(), [])
        ;

        $manager
            ->setDataTable($table)
            ->setExporterName('test')
            ->getExport()  // This triggers the call to export()
        ;
    }
}
