<?php
/**
 * Created by PhpStorm.
 * User: robbert
 * Date: 9/11/17
 * Time: 2:32 AM
 */

namespace Omines\DatatablesBundle\Processor\Doctrine\ORM;


use Doctrine\ORM\QueryBuilder;

interface QueryBuilderAwareInterface
{
    function setQueryBuilder(QueryBuilder $qb);
}