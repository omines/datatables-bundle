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
use Doctrine\ORM\Query\Expr\Comparison;
use Omines\DataTablesBundle\DataTableState;
use Omines\DataTablesBundle\Column\BoolColumn;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\Column\NumberColumn;
use Omines\DataTablesBundle\Column\AbstractColumn;

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
        $this->processSearchColumns($queryBuilder, $state);
        $this->processGlobalSearch($queryBuilder, $state);
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param DataTableState $state
     */
    private function processSearchColumns(QueryBuilder $queryBuilder, DataTableState $state)
    {
        foreach ($state->getSearchColumns() as $searchInfo) {
            /** @var AbstractColumn $column */
            $column = $searchInfo['column'];
            $search = $searchInfo['search'];

            if (!empty($search) && null !== ($filter = $column->getFilter())) {
                $queryBuilder->andWhere(new Comparison($column->getField(), $filter->getOperator(), $search));
            }
        }
    }

    /**
     * @param QueryBuilder $queryBuilder
     * @param DataTableState $state
     */
    private function processGlobalSearch(QueryBuilder $queryBuilder, DataTableState $state)
    {
        if (!empty($globalSearchOrig = $state->getGlobalSearch())) {
            $expr = $queryBuilder->expr();
            $comparisons = $expr->orX();
            foreach ($state->getDataTable()->getColumns() as $column) {
                if ($column->isGlobalSearchable() && !empty($field = $column->getField())) {
                    $globalSearch = strtolower($globalSearchOrig);
                    // dont include in global search
                    if (($column instanceof NumberColumn) && !is_numeric($globalSearch)) continue;
                    if (($column instanceof BoolColumn)) {
                        if ($globalSearch == $column->getTrueValue()) $globalSearch = true;
                        else if ($globalSearch == $column->getFalseValue()) $globalSearch = false;
                        else continue;
                    }
                    $filter = $column->getFilter();
                    if ($filter) {
                        $comparisons->add(new Comparison($field, $filter->getOperator(), $expr->literal($globalSearch)));
                    } else {
                        if ($column instanceof TextColumn) {
                            $comparisons->add($expr->like('LOWER('.$field.')', $expr->literal("%{$globalSearch}%")));
                        } else {
                            $comparisons->add($expr->eq($field, $expr->literal($globalSearch)));
                        }
                    }
                }
            }
            $queryBuilder->andWhere($comparisons);
        }
    }
}
