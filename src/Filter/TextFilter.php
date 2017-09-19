<?php
/**
 * Created by PhpStorm.
 * User: robbert
 * Date: 8/31/17
 * Time: 1:25 AM
 */

namespace Omines\DatatablesBundle\Filter;

use Symfony\Component\OptionsResolver\OptionsResolver;

class TextFilter extends AbstractFilter
{
    /** @var  string */
    protected $placeholder;

    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'template' => '@Datatables/Filter/text.html.twig',
            'placeholder' => null
        ])
            ->setAllowedTypes('placeholder', ['null', 'string']);

        return $this;
    }

    /**
     * @return string
     */
    public function getPlaceholder()
    {
        return $this->placeholder;
    }

    /**
     * @param string $placeholder
     */
    public function setPlaceholder($placeholder)
    {
        $this->placeholder = $placeholder;
    }

    public function isValidValue($value)
    {
        return true;
    }
}