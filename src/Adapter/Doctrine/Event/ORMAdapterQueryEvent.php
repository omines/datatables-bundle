<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Adapter\Doctrine\Event;

use Doctrine\ORM\Query;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class ORMAdapterQueryEvent extends Event
{
    /** @var Query */
    protected $query;

    /**
     * ORMAdapterQueryEvent constructor.
     */
    public function __construct(Query $query)
    {
        $this->query = $query;
    }

    public function getQuery(): Query
    {
        return $this->query;
    }
}
