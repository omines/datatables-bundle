<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DatatablesBundle\Processor\Doctrine\Common;

use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Omines\DatatablesBundle\Adapter\AdapterInterface;
use Omines\DatatablesBundle\Adapter\DoctrineORMAdapter;
use Omines\DatatablesBundle\DatatableState;
use Omines\DatatablesBundle\Processor\ProcessorInterface;

class CriteriaProcessor implements ProcessorInterface
{
    /**
     * @param AdapterInterface $adapter
     * @return Criteria
     */
    public function process(AdapterInterface $adapter, DatatableState $state)
    {
        /** @param DoctrineORMAdapter $adapter */
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
