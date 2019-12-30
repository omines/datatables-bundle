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
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Omines\DataTablesBundle\Exporter\DataTableExporterEvents;
use Omines\DataTablesBundle\Exporter\Event\DataTableExporterResponseEvent;
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
            ])
            ->add('lastName', TextColumn::class)
            ->createAdapter(ORMAdapter::class, [
                'entity' => Person::class,
                'query' => function (QueryBuilder $builder) {
                    $builder
                        ->select('p')
                        ->from(Person::class, 'p')
                        ->setMaxResults(5)
                        ->orderBy('p.id', 'ASC');
                },
            ])
            ->addEventListener(DataTableExporterEvents::PRE_RESPONSE, function (DataTableExporterResponseEvent $e) {
                $response = $e->getResponse();
                $response->deleteFileAfterSend(false);
                $ext = $response->getFile()->getExtension();
                $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, 'custom_filename.' . $ext);
            })
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('@App/exporter.html.twig', [
           'datatable' => $table,
       ]);
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
            ])
            ->addEventListener(DataTableExporterEvents::PRE_RESPONSE, function (DataTableExporterResponseEvent $e) {
                $e->getResponse()->deleteFileAfterSend(false);
            })
            ->handleRequest($request);

        if ($table->isCallback()) {
            return $table->getResponse();
        }

        return $this->render('@App/exporter.html.twig', [
            'datatable' => $table,
        ]);
    }
}
