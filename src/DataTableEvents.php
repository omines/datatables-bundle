<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle;

/**
 * Available events.
 *
 * @author Jeroen van den Broek <jeroen.van.den.broek@omines.com>
 */
final class DataTableEvents
{
    /**
     * The PRE_RESPONSE event is dispatched right before the response
     * is generated to allow any last-minute changes based on the table state.
     *
     * @Event("Omines\DataTablesBundle\Event\DataTablePreResponseEvent")
     */
    public const PRE_RESPONSE = 'omines_datatables.pre_response';

    /**
     * The POST_RESPONSE event is dispatched right after the response
     * is generated but before it is returned.
     *
     * @Event("Omines\DataTablesBundle\Event\DataTablePostResponseEvent")
     */
    public const POST_RESPONSE = 'omines_datatables.post_response';
}
