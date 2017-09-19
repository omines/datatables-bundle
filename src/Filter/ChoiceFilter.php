<?php
/**
 * Created by PhpStorm.
 * User: robbert
 * Date: 8/31/17
 * Time: 1:25 AM
 */

namespace Omines\DatatablesBundle\Filter;

use Symfony\Component\OptionsResolver\OptionsResolver;

class ChoiceFilter extends AbstractFilter
{
    /** @var  string */
    protected $placeholder;
    protected $choices;

    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'template' => '@Datatables/Filter/select.html.twig',
            'placeholder' => null,
            'choices' => [],
        ])
            ->setAllowedTypes('placeholder', ['null', 'string'])
            ->setAllowedTypes('choices', ['array']);

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

    /**
     * @return mixed
     */
    public function getChoices()
    {
        return $this->choices;
    }

    /**
     * @param mixed $choices
     */
    public function setChoices($choices)
    {
        $this->choices = $choices;
    }

    public function isValidValue($value)
    {
        return in_array($value, $this->choices);
    }
}