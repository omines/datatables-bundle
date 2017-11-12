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

use Symfony\Component\OptionsResolver\OptionsResolver;

abstract class AbstractEvent
{
    /** @var string */
    protected $type;

    /** @var string */
    protected $template;

    /** @var array */
    protected $vars;

    /**
     * @param array $options
     */
    public function set(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        foreach ($resolver->resolve($options) as $key => $value) {
            $this->$key = $value;
        }
    }

    /**
     * @param OptionsResolver $resolver
     * @return $this
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'type' => null,
            'template' => null,
            'vars' => [],
        ])
            ->setAllowedTypes('type', 'string')
            ->setAllowedTypes('template', 'string')
            ->setAllowedTypes('vars', 'array');

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return array
     */
    public function getVars()
    {
        return $this->vars;
    }
}
