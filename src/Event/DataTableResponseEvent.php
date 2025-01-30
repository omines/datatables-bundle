<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Event;

use Symfony\Component\HttpFoundation\Response;

/**
 * DataTableResponseEvent.
 *
 * @author Jeroen van den Broek <jeroen.van.den.broek@omines.com>
 */
abstract class DataTableResponseEvent extends DataTableEvent
{
    public function getResponse(): Response
    {
        return $this->table->getResponse();
    }
}
