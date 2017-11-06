<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Adapter\Doctrine\ORM;

use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\DataTableState;

/**
 * QueryBuilderProviderInterface.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
interface QueryBuilderProcessorInterface
{
    public function process(QueryBuilder $queryBuilder, DataTableState $state);
}
