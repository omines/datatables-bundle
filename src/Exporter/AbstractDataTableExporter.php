<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Exporter;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Default implementation for DataTableExporterInterface.
 */
abstract class AbstractDataTableExporter implements DataTableExporterInterface
{
    #[\Override]
    public function configureColumnOptions(OptionsResolver $resolver): void
    {
    }

    public function supportsRawData(): bool
    {
        return false;
    }
}
