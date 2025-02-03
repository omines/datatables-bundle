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

use Omines\DataTablesBundle\DataTable;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * DataTableEvent.
 *
 * @author Jeroen van den Broek <jeroen.van.den.broek@omines.com>
 */
abstract class DataTableEvent extends Event
{
    /**
     * DataTableEvent constructor.
     */
    public function __construct(
        protected readonly DataTable $table,
    ) {
    }

    public function getTable(): DataTable
    {
        return $this->table;
    }
}
