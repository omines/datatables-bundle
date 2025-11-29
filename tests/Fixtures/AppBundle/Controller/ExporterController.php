<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Fixtures\AppBundle\Controller;

use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\BoolColumn;
use Omines\DataTablesBundle\Column\DateTimeColumn;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableFactory;
use Omines\DataTablesBundle\Exporter\DataTableExporterEvents;
use Omines\DataTablesBundle\Exporter\Event\DataTableExporterResponseEvent;
use OpenSpout\Common\Entity\Style\Style;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Tests\Fixtures\AppBundle\Entity\Person;

/**
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class ExporterController extends AbstractController
{
    public function exportAction(Request $request, DataTableFactory $dataTableFactory): Response
    {
        $table = $dataTableFactory
            ->create()
            ->add('firstName', TextColumn::class, [
                'render' => function (string $value, Person $context) {
                    return '<a href="http://example.org">' . $value . '</a>';
                },
                // We also test the exporter specific options
                'exporterOptions' => [
                    'excel-openspout' => [
                        'style' => (new Style())->setFontItalic(),
                        'columnWidth' => 20,
                    ],
                ],
            ])
            ->add('lastName', TextColumn::class, [
                'exporterOptions' => [
                    'excel-openspout' => [
                        'style' => fn (mixed $value) => (new Style())->setFontBold(),  // We can also use a callable
                        'columnWidth' => 30,
                    ],
                ],
            ])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Person::class,
                'query' => function (QueryBuilder $builder) {
                    $builder
                        ->select('p')
                        ->from(Person::class, 'p')
                        ->setMaxResults(5)
                        ->orderBy('p.id', 'ASC');
                },
            ]);

        $this->setExportFileNameAndDeleteFileAfterSend($table);

        return $this->handleRequestAndGetResponse($table, $request);
    }

    public function exportEmptyDataTableAction(Request $request, DataTableFactory $dataTableFactory): Response
    {
        $table = $dataTableFactory
            ->create()
            ->add('firstName', TextColumn::class, [
                'render' => function (string $value, Person $context) {
                    return '<a href="http://example.org">' . $value . '</a>';
                },
            ])
            ->add('lastName', TextColumn::class)
            ->createAdapter(ORMAdapter::class, [
                'entity' => Person::class,
                'query' => function (QueryBuilder $builder) {
                    $builder
                        ->select('p')
                        ->from(Person::class, 'p')
                        ->where('p.firstName = :firstName')
                        ->setParameter('firstName', 'This user does not exist.')
                    ;
                },
            ]);

        $this->setExportFileNameAndDeleteFileAfterSend($table);

        return $this->handleRequestAndGetResponse($table, $request);
    }

    /**
     * This route returns data which does not fit in an Excel cell (cells have a character limit of 32767).
     */
    public function exportLongText(Request $request, DataTableFactory $dataTableFactory): Response
    {
        $longText = str_repeat('a', 40000);

        $table = $dataTableFactory
            ->create()
            ->add('longText', TextColumn::class)
            ->createAdapter(ArrayAdapter::class, [
                ['longText' => $longText],
            ]);

        $this->setExportFileNameAndDeleteFileAfterSend($table);

        return $this->handleRequestAndGetResponse($table, $request);
    }

    /**
     * This route returns data with HTML special characters.
     */
    public function exportSpecialChars(Request $request, DataTableFactory $dataTableFactory): Response
    {
        $table = $dataTableFactory
            ->create()
            ->add('specialChars', TextColumn::class)
            ->createAdapter(ArrayAdapter::class, [
                ['specialChars' => '<?xml version="1.0" encoding="UTF-8"?><hello>World</hello>'],
            ]);

        $this->setExportFileNameAndDeleteFileAfterSend($table);

        return $this->handleRequestAndGetResponse($table, $request);
    }

    public function exportWithTypes(Request $request, DataTableFactory $factory): Response
    {
        $enableRawExport = ['enableRawExport' => true];
        $table = $factory
            ->create()
            ->add('stringColumn', TextColumn::class)
            ->add('stringColumnWithTags', TextColumn::class, ['raw' => true])
            ->add('integerColumn', NumberColumn::class, $enableRawExport)
            ->add('floatColumn', NumberColumn::class, $enableRawExport)
            ->add('boolColumn', BoolColumn::class, $enableRawExport)
            ->add('dateTimeColumn', DateTimeColumn::class, $enableRawExport)
            ->add('nullColumn', TextColumn::class, $enableRawExport)
            ->add('typeWithToStringColumn', TextColumn::class, $enableRawExport)
            ->add('typeWithoutToStringColumn', TextColumn::class, $enableRawExport)
            ->add('stringColumnWithoutStripTags', TextColumn::class, [
                'exporterOptions' => [
                    'excel-openspout' => [
                        'stripTags' => false,
                    ],
                ],
            ])
            ->createAdapter(ArrayAdapter::class, [
                [
                    'stringColumn' => 'stringValue',
                    'stringColumnWithTags' => '<a href="https://example.org">link with special character &lt;</a>',
                    'integerColumn' => 1,
                    'floatColumn' => 1.1,
                    'boolColumn' => true,
                    'dateTimeColumn' => new \DateTimeImmutable('2021-01-01 00:00:00'),
                    'nullColumn' => null,
                    'typeWithToStringColumn' => new class implements \Stringable {
                        public function __toString(): string
                        {
                            return 'toStringValue';
                        }
                    },
                    'typeWithoutToStringColumn' => new class {},
                    'stringColumnWithoutStripTags' => '<a href="https://example.org">link with special character &lt;</a>',
                ],
            ])
        ;

        $this->setExportFileNameAndDeleteFileAfterSend($table);

        return $this->handleRequestAndGetResponse($table, $request);
    }

    private function setExportFileNameAndDeleteFileAfterSend(DataTable $table): void
    {
        $table->addEventListener(DataTableExporterEvents::PRE_RESPONSE, function (DataTableExporterResponseEvent $e) {
            $response = $e->getResponse();
            $response->deleteFileAfterSend(false);
            $ext = $response->getFile()->getExtension();
            $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'custom_filename.' . $ext);
        });
    }

    private function handleRequestAndGetResponse(DataTable $table, Request $request): Response
    {
        $table->handleRequest($request);

        return $table->isCallback() ? $table->getResponse() : $this->render('@App/exporter.html.twig', [
            'datatable' => $table,
        ]);
    }
}
