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

/**
 * Available events.
 *
 * @author Maxime Pinot <contact@maximepinot.com>
 */
final class DataTableExporterEvents
{
    /**
     * The PRE_RESPONSE event is dispatched before sending
     * the BinaryFileResponse to the user.
     *
     * Note that the file is accessible through the Response object.
     * Both the file and the Response can be modified before being sent.
     *
     * @Event("Omines\DataTablesBundle\Exporter\Event\DataTableExporterResponseEvent")
     */
    const PRE_RESPONSE = 'omines_datatables.exporter.pre_response';
}
