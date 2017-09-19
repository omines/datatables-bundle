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

class TextFilter extends AbstractFilter
{
    /** @var string */
    protected $placeholder;

    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'template' => '@Datatables/Filter/text.html.twig',
            'placeholder' => null,
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
