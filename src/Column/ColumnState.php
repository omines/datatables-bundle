<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Column;

/**
 * ColumnState.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 *
 * @internal
 */
class ColumnState
{
    /** @var AbstractColumn */
    private $column;

    /**
     * ColumnState constructor.
     *
     * @param AbstractColumn $column
     */
    public function __construct(AbstractColumn $column)
    {
        $this->column = $column;
    }
}
