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

/**
 * http://m.datatables.net/reference/event/.
 */
class Event extends AbstractEvent
{
    const COLUMN_SIZING = 'column_sizing.dt';
    const COLUMN_VISIBILITY = 'column_visibility.dt';
    const DESTROY = 'destroy.dt';
    const DRAW = 'draw.dt';
    const ERROR = 'error.dt';
    const INIT = 'init.dt';
    const LENGTH = 'length.dt';
    const ORDER = 'order.dt';
    const PAGE = 'page.dt';
    const PRE_INIT = 'preInit.dt';
    const PRE_XHR = 'preXhr.dt';
    const PROCESSING = 'processing.dt';
    const SEARCH = 'search.dt';
    const STATE_LOADED = 'stateLoaded.dt';
    const STATE_LOAD_PARAMS = 'stateLoadParams.dt';
    const STATE_SAVE_PARAMS = 'stateSaveParams.dt';
    const XHR = 'xhr.dt';
}
