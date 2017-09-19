<?php
/**
 * Created by PhpStorm.
 * User: Robbert Beesems
 * Date: 7/28/2017
 * Time: 2:34 PM
 */

namespace Omines\DatatablesBundle;

use Omines\DatatablesBundle\Identity\AbstractIdentity;
use Symfony\Component\HttpFoundation\RequestStack;

class DatatableFactory
{
    /** @var  array */
    protected $settings;

    /** @var  array */
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
     * @return Datatable
     */
    public function create(array $settings = [], array $options = [])
    {
        return new Datatable(array_merge($this->settings, $settings), array_merge($this->options, $options));
    }
}