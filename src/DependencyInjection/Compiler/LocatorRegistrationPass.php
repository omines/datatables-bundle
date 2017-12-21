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

use Omines\DataTablesBundle\DataTableFactory;
use Omines\DataTablesBundle\DependencyInjection\Instantiator;
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
        $instantiator = $container->getDefinition(Instantiator::class);

        $instantiator->setArguments([
            $this->registerLocator($container, 'adapter'),
            $this->registerLocator($container, 'column'),
            $this->registerLocator($container, 'type'),
        ]);

        $container->getDefinition(DataTableFactory::class)
            ->addMethodCall('setInstantiator', [$instantiator])
        ;
    }

    /**
     * @param ContainerBuilder $container
     * @param string $baseTag
     * @return Definition
     */
    private function registerLocator(ContainerBuilder $container, string $baseTag): Definition
    {
        $types = [];
        foreach ($container->findTaggedServiceIds("datatables.{$baseTag}") as $serviceId => $tag) {
            $types[$serviceId] = new Reference($serviceId);
        }

        return $container
            ->register("datatables.{$baseTag}_locator", ServiceLocator::class)
            ->addTag('container.service_locator')
            ->setArguments([$types])
        ;
    }
}
