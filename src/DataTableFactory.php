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
    protected $typeLocator;

    /** @var array */
    protected $settings;

    /** @var array */
    protected $options;

    /**
     * DataTableFactory constructor.
     *
     * @param ServiceLocator $typeLocator
     * @param array $settings
     * @param array $options
     */
    public function __construct(ServiceLocator $typeLocator, array $settings, array $options)
    {
        $this->typeLocator = $typeLocator;
        $this->settings = $settings;
        $this->options = $options;
    }

    /**
     * @param array $settings
     * @param array $options
     * @param DataTableState $state
     * @return DataTable
     */
    public function create(array $settings = [], array $options = [], DataTableState $state = null)
    {
        return new DataTable(array_merge($this->settings, $settings), array_merge($this->options, $options), $state);
    }

    /**
     * @param string $name
     * @param array $settings
     * @param array $options
     * @param DataTableState|null $state
     * @return DataTable
     */
    public function createFromType(string $name, array $settings = [], array $options = [], DataTableState $state = null)
    {
        $dataTable = $this->create($settings, $options, $state);

        if ($this->typeLocator->has($name)) {
            $type = $this->typeLocator->get($name);
        } elseif (class_exists($name) && in_array(DataTableTypeInterface::class, class_implements($name), true)) {
            $type = new $name();
        } else {
            throw new \InvalidArgumentException(sprintf('Could not load type "%s"', $name));
        }

        /* @var DataTableTypeInterface $type */
        $type->configure($dataTable);

        return $dataTable;
    }
}
