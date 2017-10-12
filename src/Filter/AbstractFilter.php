<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DatatablesBundle\Filter;

use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractFilter
{
    /** @var string */
    protected $template_html;

    /** @var string */
    protected $template_js;

    /** @var string */
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
            'template_html' => null,
            'template_js' => null,
            'operator' => 'CONTAINS',
        ]);

        return $this;
    }

    /**
     * @return string
     */
    public function getTemplateHtml()
    {
        return $this->template_html;
    }

    /**
     * @param string $template_html
     */
    public function setTemplateHtml($template_html)
    {
        $this->template_html = $template_html;
    }

    /**
     * @return string
     */
    public function getTemplateJs()
    {
        return $this->template_js;
    }

    /**
     * @param string $template_js
     */
    public function setTemplateJs($template_js)
    {
        $this->template_js = $template_js;
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

    abstract public function isValidValue($value);
}
