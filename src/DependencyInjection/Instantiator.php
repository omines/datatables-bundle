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
use Omines\DataTablesBundle\Exception\InvalidArgumentException;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * The instantiator service handles lazy instantiation of services and/or ad hoc instantiation of objects.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 *
 * @phpstan-type SupportedTypes AdapterInterface|AbstractColumn|DataTableTypeInterface
 */
class Instantiator
{
    /** @var ServiceLocator<SupportedTypes>[] */
    private array $locators;

    /**
     * Instantiator constructor.
     *
     * @param ServiceLocator<SupportedTypes>[] $locators
     */
    public function __construct(array $locators = [])
    {
        $this->locators = $locators;
    }

    public function getAdapter(string $type): AdapterInterface
    {
        return $this->getInstance($type, AdapterInterface::class);
    }

    public function getColumn(string $type): AbstractColumn
    {
        return $this->getInstance($type, AbstractColumn::class);
    }

    public function getType(string $type): DataTableTypeInterface
    {
        return $this->getInstance($type, DataTableTypeInterface::class);
    }

    /**
     * @template T
     * @param class-string<T> $baseType
     * @return T
     */
    private function getInstance(string $type, string $baseType)
    {
        if (isset($this->locators[$baseType]) && $this->locators[$baseType]->has($type)) {
            $instance = $this->locators[$baseType]->get($type);
        } elseif (class_exists($type)) {
            $instance = new $type();
        } else {
            throw new InvalidArgumentException(sprintf('Could not resolve type "%s" to a service or class, are you missing a use statement? Or is it implemented but does it not correctly derive from "%s"?', $type, $baseType));
        }
        if (!$instance instanceof $baseType) {
            throw new InvalidArgumentException(sprintf('Class "%s" must implement/extend %s', $type, $baseType));
        }

        return $instance;
    }
}
