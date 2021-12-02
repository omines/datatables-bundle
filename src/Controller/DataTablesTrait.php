<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Controller;

use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableFactory;
use Psr\Container\ContainerInterface;

/**
 * DataTablesTrait.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 *
 * @property ContainerInterface $container
 *
 * @deprecated inject the DataTableFactory in your controllers or actions instead
 */
trait DataTablesTrait
{
    /**
     * Creates and returns a basic DataTable instance.
     *
     * @param array $options Options to be passed
     * @return DataTable
     */
    protected function createDataTable(array $options = [])
    {
        @trigger_error('Omines\DataTablesBundle\Controller\DataTablesTrait is deprecated. Use dependency injection to inject the Omines\DataTablesBundle\DataTableFactory service instead.', E_USER_DEPRECATED);

        return $this->container->get(DataTableFactory::class)->create($options);
    }

    /**
     * Creates and returns a DataTable based upon a registered DataTableType or an FQCN.
     *
     * @param string $type FQCN or service name
     * @param array $typeOptions Type-specific options to be considered
     * @param array $options Options to be passed
     * @return DataTable
     */
    protected function createDataTableFromType($type, array $typeOptions = [], array $options = [])
    {
        @trigger_error('Omines\DataTablesBundle\Controller\DataTablesTrait is deprecated. Use dependency injection to inject the Omines\DataTablesBundle\DataTableFactory service instead.', E_USER_DEPRECATED);

        return $this->container->get(DataTableFactory::class)->createFromType($type, $typeOptions, $options);
    }
}
