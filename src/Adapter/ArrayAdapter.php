<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Adapter;

use Omines\DataTablesBundle\DataTableState;

/**
 * ArrayAdapter.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class ArrayAdapter implements AdapterInterface
{
    /** @var array */
    private $data = [];

    /**
     * {@inheritdoc}
     */
    public function configure(array $options)
    {
        $this->data = $options;
    }

    /**
     * {@inheritdoc}
     */
    public function getData(DataTableState $state): ResultSetInterface
    {
        // TODO: Apply search
        return new ArrayResultSet(array_slice($this->data, $state->getStart(), $state->getLength()), count($this->data));
    }
}
