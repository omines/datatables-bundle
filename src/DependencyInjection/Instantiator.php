<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\DependencyInjection;

use Omines\DataTablesBundle\Adapter\AdapterInterface;
use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * The instantiator service handles lazy instantiation of services and/or ad hoc instantiation of objects.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class Instantiator
{
    /** @var ServiceLocator */
    private $adapterLocator;

    /** @var ServiceLocator */
    private $columnLocator;

    /** @var ServiceLocator */
    private $typeLocator;

    /**
     * Instantiator constructor.
     *
     * @param ServiceLocator $adapterLocator
     * @param ServiceLocator $columnLocator
     * @param ServiceLocator $typeLocator
     */
    public function __construct(ServiceLocator $adapterLocator, ServiceLocator $columnLocator, ServiceLocator $typeLocator)
    {
        $this->adapterLocator = $adapterLocator;
        $this->columnLocator = $columnLocator;
        $this->typeLocator = $typeLocator;
    }

    /**
     * @param string $type
     * @return AdapterInterface
     */
    public function getAdapter(string $type): AdapterInterface
    {
        return $this->getInstance($this->adapterLocator, $type, AdapterInterface::class);
    }

    /**
     * @param string $type
     * @return AbstractColumn
     */
    public function getColumn(string $type): AbstractColumn
    {
        return $this->getInstance($this->columnLocator, $type, AbstractColumn::class);
    }

    /**
     * @param string $type
     * @return DataTableTypeInterface
     */
    public function getType(string $type): DataTableTypeInterface
    {
        return $this->getInstance($this->typeLocator, $type, DataTableTypeInterface::class);
    }

    /**
     * @param ServiceLocator $locator
     * @param string $type
     * @param string $baseType
     * @return mixed
     */
    private function getInstance(ServiceLocator $locator, string $type, string $baseType)
    {
        if ($locator->has($type)) {
            return $locator->get($type);
        } elseif (class_exists($type) && is_subclass_of($type, $baseType)) {
            return new $type();
        }
        throw new \InvalidArgumentException(sprintf('Could not resolve type "%s" to a service or class, is it implemented and does it correctly derive from "%s"?', $type, $baseType));
    }
}
