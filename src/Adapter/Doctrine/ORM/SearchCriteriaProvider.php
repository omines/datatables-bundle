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

use Doctrine\ORM\Query\Expr\Comparison;
use Doctrine\ORM\QueryBuilder;
use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\DataTableState;

/**
 * SearchCriteriaProvider.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class SearchCriteriaProvider implements QueryBuilderProcessorInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(QueryBuilder $queryBuilder, DataTableState $state)
    {
        $expr = $queryBuilder->expr();
        foreach ($state->getSearchColumns() as $searchInfo) {
            /** @var AbstractColumn $column */
            $column = $searchInfo['column'];
            $search = $searchInfo['search'];

            if (!empty($search) && null !== ($filter = $column->getFilter())) {
                $queryBuilder->andWhere(new Comparison($column->getField(), $filter->getOperator(), $search));
            }
        }

        if (!empty($globalSearch = $state->getGlobalSearch())) {
            $comparisons = $expr->orX();
            foreach ($state->getDataTable()->getColumns() as $column) {
                if ($column->isGlobalSearchable() && !empty($field = $column->getField())) {
                    $comparisons->add($expr->like($field, "'%{$globalSearch}%'"));
                }
            }
            $queryBuilder->andWhere($comparisons);
        }
    }
}
