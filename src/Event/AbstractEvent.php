<?php
/**
 * Created by PhpStorm.
 * User: robbert
 * Date: 8/30/17
 * Time: 1:31 AM
 */

namespace Omines\DatatablesBundle\Event;


use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractEvent
{
    /** @var  string */
    protected $type;

    /** @var  string */
    protected $template;

    /** @var  array */
    protected $vars;

    /** @var array */
    protected $options;

    /**
     * AbstractEvent constructor.
     */
    public function __construct()
    {
        $this->options = [];
    }

    /**
     * @param array $options
     */
    public function set(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $accessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableMagicCall()
            ->getPropertyAccessor();

        foreach ($this->options as $setter => $value) {
            $accessor->setValue($this, $setter, $value);
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
     * @param string $type
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }

    /**
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @param string $template
     */
    public function setTemplate(string $template)
    {
        $this->template = $template;
    }

    /**
     * @return array
     */
    public function getVars()
    {
        return $this->vars;
    }

    /**
     * @param array $vars
     */
    public function setVars(array $vars)
    {
        $this->vars = $vars;
    }
}