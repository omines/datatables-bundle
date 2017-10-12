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

class ChoiceFilter extends AbstractFilter
{
    /** @var string */
    protected $placeholder;
    protected $choices;

    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'template_html' => '@Datatables/Filter/select.html.twig',
            'template_js' => '@Datatables/Filter/select.js.twig',
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
        return array_key_exists($value, $this->choices);
    }
}
