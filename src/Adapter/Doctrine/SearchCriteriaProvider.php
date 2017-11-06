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
        foreach ($state->getColumns() as $column) {
            if ($column->isSearchable() && null !== $column->getSearchValue() && null !== $column->getFilter()) {
                $criteria->andWhere(new Comparison($column->getField(), $column->getFilter()->getOperator(), $column->getSearchValue()));
            }

            if ($column->isGlobalSearchable() && null !== $state->getSearch() && null !== $column->getFilter()) {
                $criteria->andWhere(new Comparison($column->getField(), $column->getFilter()->getOperator(), $state->getSearch()));
            }
        }

        return $criteria;
    }
}
