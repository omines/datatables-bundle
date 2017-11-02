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

use Omines\DataTablesBundle\Column\Column;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableFactory;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * HomeController.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class HomeController extends Controller
{
    public function showAction()
    {
        /** @var DataTable $datatable1 */
        $datatable1 = $this->get(DataTableFactory::class)->create();
        $datatable1
            ->column(Column::class, ['label' => 'foo', 'field' => 'bar'])
            ->column(Column::class, ['label' => 'bar', 'field' => 'foo', 'name' => 'test'])
        ;

        /** @var DataTable $datatable2 */
        $datatable2 = $this->get(DataTableFactory::class)->create([
            'languageFromCdn' => false,
        ]);
        $datatable2
            ->column(Column::class, ['label' => 'foo', 'field' => 'bar'])
            ->column(Column::class, ['label' => 'bar', 'field' => 'foo', 'name' => 'test'])
        ;

        return $this->render('@App/home.html.twig', [
            'datatable1' => $datatable1,
            'datatable2' => $datatable2,
        ]);
    }
}
