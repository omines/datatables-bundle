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

class DataTableFactory
{
    /** @var array */
    protected $settings;

    /** @var array */
    protected $options;

    /**
     * DataTableFactory constructor.
     * @param array $settings
     * @param array $options
     */
    public function __construct(array $settings, array $options)
    {
        $this->settings = $settings;
        $this->options = $options;
    }

    /**
     * @param array $settings
     * @param array $options
     * @param DataTableState $state
     * @return DataTable
     */
    public function create(array $settings = [], array $options = [], DataTableState $state = null)
    {
        return new DataTable(array_merge($this->settings, $settings), array_merge($this->options, $options), $state);
    }
}
