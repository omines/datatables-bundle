<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Exporter\Event;

use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * DataTableExporterResponseEvent.
 *
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class DataTableExporterResponseEvent extends Event
{
    /** @var BinaryFileResponse */
    private $response;

    /**
     * DataTableExporterResponseEvent constructor.
     */
    public function __construct(BinaryFileResponse $response)
    {
        $this->response = $response;
    }

    public function getResponse(): BinaryFileResponse
    {
        return $this->response;
    }
}
