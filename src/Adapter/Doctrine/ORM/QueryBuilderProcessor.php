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

class QueryBuilderProcessor implements QueryBuilderProcessorInterface
{
    private $callable;

    public function __construct(callable $value)
    {
        $this->callable = $value;
    }

    public function process(QueryBuilder $queryBuilder, DataTableState $state)
    {
        return call_user_func($this->callable, $queryBuilder, $state);
    }
}
