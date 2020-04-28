<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\DependencyInjection\Compiler;

use Omines\DataTablesBundle\Adapter\AdapterInterface;
use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\DataTableTypeInterface;
use Omines\DataTablesBundle\DependencyInjection\Instantiator;
use Omines\DataTablesBundle\Exporter\DataTableExporterInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * LocatorRegistrationPass.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class LocatorRegistrationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $container->getDefinition(Instantiator::class)
            ->setArguments([[
                AdapterInterface::class => $this->registerLocator($container, 'adapter'),
                AbstractColumn::class => $this->registerLocator($container, 'column'),
                DataTableTypeInterface::class => $this->registerLocator($container, 'type'),
                DataTableExporterInterface::class => $this->registerLocator($container, 'exporter'),
            ]]);
    }

    private function registerLocator(ContainerBuilder $container, string $baseTag): Definition
    {
        $types = [];
        foreach ($container->findTaggedServiceIds("datatables.{$baseTag}") as $serviceId => $tag) {
            $types[$serviceId] = new Reference($serviceId);
        }

        return $container
            ->register("datatables.{$baseTag}_locator", ServiceLocator::class)
            ->addTag('container.service_locator')
            ->setPublic(false)
            ->setArguments([$types])
        ;
    }
}
