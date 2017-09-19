<?php
/**
 * Created by PhpStorm.
 * User: robbert
 * Date: 9/10/17
 * Time: 11:49 PM
 */

namespace Omines\DatatablesBundle\Processor\Doctrine\Common;


use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Expr\Comparison;
use Omines\DatatablesBundle\Adapter\AdapterInterface;
use Omines\DatatablesBundle\Adapter\DoctrineORMAdapter;
use Omines\DatatablesBundle\Processor\ProcessorInterface;

class CriteriaProcessor implements ProcessorInterface
{
    /**
     * @param AdapterInterface $adapter
     * @return Criteria
     */
    public function process(AdapterInterface $adapter)
    {
        /** @param DoctrineORMAdapter $adapter */

        $criteria = Criteria::create();

        foreach ($adapter->getState()->getColumns() as $column) {
            if ($column->isSearchable() && $column->getSearchValue() != null && $column->getFilter() != null) {
                $criteria->andWhere(new Comparison($column->getField(), $column->getFilter()->getOperator(), $column->getSearchValue()));
            }

            if ($column->isGlobalSearchable() && $adapter->getState()->getSearch() != null && $column->getFilter() != null) {
                $criteria->andWhere(new Comparison($column->getField(), $column->getFilter()->getOperator(), $adapter->getState()->getSearch()));
            }
        }

        return $criteria;
    }
}