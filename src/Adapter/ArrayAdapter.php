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
    private $data;

    /**
     * ArrayAdapter constructor.
     *
     * @param array $data Optional initial data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

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
    public function handleState(DataTableState $state)
    {
        // TODO: Implement handleState() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalRecords()
    {
        return count($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function getTotalDisplayRecords()
    {
        return count($this->data);
    }

    /**
     * {@inheritdoc}
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param array $data
     * @return self
     */
    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function mapRow($columns, $row, $addIdentifier)
    {
        return $row;
    }
}
