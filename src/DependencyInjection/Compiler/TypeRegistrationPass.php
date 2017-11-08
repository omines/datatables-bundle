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
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

/**
 * TypeRegistrationPass.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class TypeRegistrationPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        // Inject tagged types into the factory
        $types = [];
        foreach ($container->findTaggedServiceIds('datatables.type') as $serviceId => $tag) {
            $types[$serviceId] = new Reference($serviceId);
        }

        $locator = $container
            ->register('datatables.type_locator', ServiceLocator::class)
            ->addTag('container.service_locator')
            ->setArguments([$types])
        ;

        $container->getDefinition(DataTableFactory::class)
            ->addMethodCall('setTypeLocator', [$locator]);
    }
}
