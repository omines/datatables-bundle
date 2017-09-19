<?php
/**
 * Created by PhpStorm.
 * User: robbert
 * Date: 9/6/17
 * Time: 8:28 AM
 */

namespace Omines\DatatablesBundle\Processor;


use Omines\DatatablesBundle\Adapter\AdapterInterface;
use Omines\DatatablesBundle\DatatableState;

interface ProcessorInterface
{
    /**
     * @param AdapterInterface $adapter
     * @return mixed
     */
    function process(AdapterInterface $adapter);
}