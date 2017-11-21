<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle;

use Symfony\Component\DependencyInjection\ServiceLocator;

class DataTableFactory
{
    /** @var ServiceLocator */
    protected $adapterLocator;

    /** @var ServiceLocator */
    protected $typeLocator;

    /** @var array<string, DataTableTypeInterface> */
    protected $resolvedTypes = [];

    /** @var array */
    protected $settings;

    /** @var array */
    protected $options;

    /**
     * DataTableFactory constructor.
     *
     * @param array $settings
     * @param array $options
     */
    public function __construct(array $settings, array $options)
    {
        $this->settings = $settings;
        $this->options = $options;
    }

    /**
     * @param ServiceLocator $adapterLocator
     */
    public function setAdapterLocator(ServiceLocator $adapterLocator)
    {
        $this->adapterLocator = $adapterLocator;
    }

    /**
     * @param ServiceLocator $typeLocator
     */
    public function setTypeLocator(ServiceLocator $typeLocator)
    {
        $this->typeLocator = $typeLocator;
    }

    /**
     * @param array $settings
     * @param array $options
     * @param DataTableState $state
     * @return DataTable
     */
    public function create(array $settings = [], array $options = [], DataTableState $state = null)
    {
        return new DataTable(array_merge($this->settings, $settings), array_merge($this->options, $options), $state, $this->adapterLocator);
    }

    /**
     * @param string|DataTableTypeInterface $type
     * @param array $typeOptions
     * @param array $settings
     * @param array $options
     * @param DataTableState|null $state
     * @return DataTable
     */
    public function createFromType($type, array $typeOptions = [], array $settings = [], array $options = [], DataTableState $state = null)
    {
        $dataTable = $this->create($settings, $options, $state);

        if (is_string($type)) {
            $name = $type;
            if (isset($this->resolvedTypes[$name])) {
                $type = $this->resolvedTypes[$name];
            } else {
                $this->resolvedTypes[$name] = $type = $this->resolveType($name);
            }
        }

        $type->configure($dataTable, $typeOptions);

        return $dataTable;
    }

    /**
     * Resolves a dynamic type to an instance via services or instantiation.
     *
     * @param string $type
     * @return DataTableTypeInterface
     */
    private function resolveType(string $type): DataTableTypeInterface
    {
        if (null !== $this->typeLocator && $this->typeLocator->has($type)) {
            return $this->typeLocator->get($type);
        } elseif (class_exists($type) && in_array(DataTableTypeInterface::class, class_implements($type), true)) {
            return new $type();
        }
        throw new \InvalidArgumentException(sprintf('Could not resolve type "%s" to a service or class', $type));
    }
}
