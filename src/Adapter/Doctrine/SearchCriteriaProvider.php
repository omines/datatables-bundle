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
use Doctrine\Common\Collections\Expr\Comparison;
use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\DataTableState;

/**
 * SearchCriteriaProcessor.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class SearchCriteriaProvider implements CriteriaProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(DataTableState $state)
    {
        $criteria = Criteria::create();
        foreach ($state->getSearchColumns() as $searchInfo) {
            /** @var AbstractColumn $column */
            $column = $searchInfo['column'];
            $search = $searchInfo['search'];

            if ($column->isSearchable() && !empty($search) && null !== $column->getFilter()) {
                $this->addSearch($criteria, $column, $search);
            }
        }

        if (!empty($globalSearch = $state->getGlobalSearch())) {
            foreach ($state->getDataTable()->getColumns() as $column) {
                if ($column->isGlobalSearchable() && null !== $state->getGlobalSearch() && null !== $column->getFilter()) {
                    $this->addSearch($criteria, $column, $globalSearch);
                }
            }
        }

        return $criteria;
    }

    private function addSearch(Criteria $criteria, AbstractColumn $column, string $search)
    {
        $criteria->andWhere(new Comparison($column->getField(), $column->getFilter()->getOperator(), $search));
    }
}
