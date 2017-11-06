<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Adapter\Doctrine;

use Doctrine\Common\Collections\Criteria;
use Omines\DataTablesBundle\DataTableState;

/**
 * CriteriaProviderInterface.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
interface CriteriaProviderInterface
{
    /**
     * @param DataTableState $state
     * @return Criteria|null
     */
    public function process(DataTableState $state);
}
