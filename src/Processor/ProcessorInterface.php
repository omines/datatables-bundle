<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Processor;

use Omines\DataTablesBundle\Adapter\AdapterInterface;
use Omines\DataTablesBundle\DataTableState;

interface ProcessorInterface
{
    /**
     * @param AdapterInterface $adapter
     * @param DataTableState $state
     * @return mixed
     */
    public function process(AdapterInterface $adapter, DataTableState $state);
}
