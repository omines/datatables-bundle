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

use Omines\DataTablesBundle\Adapter\ArrayAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TranslationController.
 */
class TranslationController extends AbstractController
{
    public function tableAction(Request $request, DataTableFactory $dataTableFactory, TranslatorInterface $translator)
    {
        // override default "en" fallback locale
        $translator->setFallbackLocales([$request->getLocale()]);

        $datatable = $dataTableFactory->create();
        $datatable
            ->setName($request->query->has('cdn') ? 'CDN' : 'noCDN')
            ->setMethod(Request::METHOD_GET)
            ->setLanguageFromCDN($request->query->has('cdn'))
            ->add('col3', TextColumn::class, ['label' => 'foo', 'field' => 'bar'])
            ->add('col4', TextColumn::class, ['label' => 'bar', 'field' => 'foo'])
            ->createAdapter(ArrayAdapter::class)
        ;

        if ($datatable->handleRequest($request)->isCallback()) {
            return $datatable->getResponse();
        }

        return $this->render('@App/table.html.twig', [
            'datatable' => $datatable,
        ]);
    }
}
