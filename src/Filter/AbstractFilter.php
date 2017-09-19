<?php

namespace Omines\DatatablesBundle\Filter;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractFilter
{

    /** @var  string */
    protected $template;

    /** @var  string */
    protected $operator;

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
            'template' => null,
            'operator' => 'CONTAINS'
        ])
           ->setAllowedTypes('template', 'string');

        return $this;
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
    public function setTemplate($template)
    {
        $this->template = $template;
    }

    /**
     * @return string
     */
    public function getOperator()
    {
        return $this->operator;
    }

    /**
     * @param string $operator
     */
    public function setOperator($operator)
    {
        $this->operator = $operator;
    }

    public abstract function isValidValue($value);
}