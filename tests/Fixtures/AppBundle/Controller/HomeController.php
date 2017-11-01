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
        $datatable = $this->get(DataTableFactory::class)->create();

        return $this->render('@App/home.html.twig', [
            'datatable' => $datatable,
        ]);
    }
}
