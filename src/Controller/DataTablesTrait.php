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
 */
trait DataTablesTrait
{
    /**
     * Creates and returns a basic DataTable instance.
     *
     * @param array $settings Settings to be applied
     * @param array $options Options to be passed
     * @return DataTable
     */
    protected function createDataTable(array $settings = [], array $options = [])
    {
        return $this->container->get(DataTableFactory::class)->create($settings, $options);
    }

    /**
     * Creates and returns a DataTable based upon a registered DataTableType or an FQCN.
     *
     * @param string $type FQCN or service name
     * @param array $settings Settings to be applied
     * @param array $options Options to be passed
     * @return DataTable
     */
    protected function createDataTableFromType($type, array $settings = [], array $options = [])
    {
        return $this->container->get(DataTableFactory::class)->createFromType($type, $settings, $options);
    }
}
