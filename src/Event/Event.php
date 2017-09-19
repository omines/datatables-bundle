<?php
/**
 * Created by PhpStorm.
 * User: robbert
 * Date: 8/30/17
 * Time: 1:15 AM
 */

namespace Omines\DatatablesBundle\Event;

/**
 * http://m.datatables.net/reference/event/
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