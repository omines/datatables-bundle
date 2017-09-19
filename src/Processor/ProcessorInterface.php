<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DatatablesBundle\Processor;

use Omines\DatatablesBundle\Adapter\AdapterInterface;

interface ProcessorInterface
{
    /**
     * @param AdapterInterface $adapter
     * @return mixed
     */
    public function process(AdapterInterface $adapter);
}
