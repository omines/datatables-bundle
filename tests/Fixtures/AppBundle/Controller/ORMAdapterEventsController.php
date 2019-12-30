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

use Omines\DataTablesBundle\Adapter\Doctrine\Event\ORMAdapterQueryEvent;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapter;
use Omines\DataTablesBundle\Adapter\Doctrine\ORMAdapterEvents;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\Fixtures\AppBundle\Entity\Employee;

/**
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class ORMAdapterEventsController extends AbstractController
{
    const PRE_QUERY_RESULT_CACHE_ID = 'datatable_result_cache';

    public function preQueryAction(Request $request, DataTableFactory $dataTableFactory): Response
    {
        $datatable = $dataTableFactory->create()
            ->add('firstName', TextColumn::class)
            ->add('lastName', TextColumn::class)
            ->add('company', TextColumn::class, ['field' => 'company.name'])
            ->createAdapter(ORMAdapter::class, [
                'entity' => Employee::class,
            ])
            ->addEventListener(ORMAdapterEvents::PRE_QUERY, function (ORMAdapterQueryEvent $event) {
                $event->getQuery()->useResultCache(true, 0, self::PRE_QUERY_RESULT_CACHE_ID);
            });

        return $datatable->handleRequest($request)->getResponse();
    }
}
