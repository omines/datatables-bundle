<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Exception;

use Omines\DataTablesBundle\Exporter\DataTableExporterInterface;

/**
 * Thrown when a DataTable exporter cannot be found.
 *
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class UnknownDataTableExporterException extends \Exception
{
    /**
     * UnknownDataTableExporterException constructor.
     *
     * @param string $name The name of the DataTable exporter that cannot be found
     * @param \Traversable $referencedExporters Available DataTable exporters
     */
    public function __construct(string $name, \Traversable $referencedExporters)
    {
        $format = <<<EOF
        Cannot find a DataTable exporter named "%s". 
        Did you forget to tag the exporter with 'datatables.exporter'?
        Referenced DataTable exporters are : %s.
EOF;

        parent::__construct(sprintf($format, $name, $this->formatReferencedExporters($referencedExporters)));
    }

    private function formatReferencedExporters(\Traversable $referencedExporters): string
    {
        $names = [];

        /** @var DataTableExporterInterface $exporter */
        foreach ($referencedExporters as $exporter) {
            $names[] = sprintf('"%s"', $exporter->getName());
        }

        return implode(', ', $names);
    }
}
