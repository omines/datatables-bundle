<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DataTablesBundle;

use Omines\DataTablesBundle\DependencyInjection\DataTablesExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DataTablesBundle extends Bundle
{
    public function getContainerExtension()
    {
        return new DataTablesExtension();
    }
}
