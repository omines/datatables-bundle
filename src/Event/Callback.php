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
 * https://datatables.net/reference/option/.
 */
class Callback extends AbstractEvent
{
    const CREATED_ROW = 'createdRow';
    const DRAW_CALLBACK = 'drawCallback';
    const FOOTER_CALLBACK = 'footerCallback';
    const FORMAT_NUMBER = 'formatNumber';
    const HEADER_CALLBACK = 'headerCallback';
    const INFO_CALLBACK = 'infoCallback';
    const INIT_COMPLETE = 'initComplete';
    const PRE_DRAW_CALLBACK = 'preDrawCallback';
    const ROW_CALLBACK = 'rowCallback';
    const STATE_LOAD_CALLBACK = 'stateLoadCallback';
    const STATE_LOADED = 'stateLoaded';
    const STATE_LOAD_PARAMS = 'stateLoadParams';
    const STATE_SAVE_CALLBACK = 'stateSaveCallback';
    const STATE_SAVE_PARAMS = 'stateSaveParams';
}
