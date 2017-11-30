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
    private $typeLocator;

    /**
     * Instantiator constructor.
     *
     * @param ServiceLocator $adapterLocator
     * @param ServiceLocator $typeLocator
     */
    public function __construct(ServiceLocator $adapterLocator, ServiceLocator $typeLocator)
    {
        $this->adapterLocator = $adapterLocator;
        $this->typeLocator = $typeLocator;
    }

    /**
     * @param string $type
     * @return AdapterInterface|null
     */
    public function getAdapter(string $type)
    {
        return $this->getInstance($this->adapterLocator, $type, AdapterInterface::class);
    }

    /**
     * @param string $type
     * @return DataTableTypeInterface|null
     */
    public function getType(string $type)
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
        } elseif (class_exists($type) && in_array($baseType, class_implements($type), true)) {
            return new $type();
        }
        throw new \InvalidArgumentException(sprintf('Could not resolve type "%s" to a service or class', $type));
    }
}
