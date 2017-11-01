<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Fixtures\Controller;

use Symfony\Component\HttpFoundation\Response;

/**
 * HomeController.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class HomeController
{
    public function showAction()
    {
        return Response::create('Test');
    }
}
