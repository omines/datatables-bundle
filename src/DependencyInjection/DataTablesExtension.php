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
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

class DataTablesExtension extends Extension
{
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);

        $options = $config['options'];
        unset($config['options']);
        $settings = $config;

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__ . '/../Resources/config'));
        $loader->load('services.yaml');

        $container->setAlias('datatables.renderer', $settings['renderer']);
        unset($settings['renderer']);

        $container->setParameter('datatables.options', $options);
        $container->setParameter('datatables.settings', $settings);

        $container->registerForAutoconfiguration(AdapterInterface::class)
            ->addTag('datatables.adapter')
            ->setShared(false);
        $container->registerForAutoconfiguration(DataTableTypeInterface::class)
            ->addTag('datatables.type');
    }

    public function getAlias()
    {
        // Default would underscore the camelcase unintuitively
        return 'datatables';
    }
}
