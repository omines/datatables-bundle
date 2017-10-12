<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DatatablesBundle;

class DatatableFactory
{
    /** @var array */
    protected $settings;

    /** @var array */
    protected $options;

    /**
     * DatatableFactory constructor.
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
     * @param DatatableState $state
     * @return Datatable
     */
    public function create(array $settings = [], array $options = [], DatatableState $state = null)
    {
        return new Datatable(array_merge($this->settings, $settings), array_merge($this->options, $options), $state);
    }
}
