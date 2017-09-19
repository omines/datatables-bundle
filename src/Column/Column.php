<?php

namespace Omines\DatatablesBundle\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;

class Column extends AbstractColumn
{
    /**
     * @param array $options
     */
    public function set(array $options)
    {
        if (!isset($options['label']) && isset($options['field'])) {
            $options['label'] = $options['field'];
        }

        if (!isset($options['orderField']) && isset($options['field'])) {
            $options['orderField'] = $options['field'];
        }

        parent::set($options);
    }

    /**
     * @param OptionsResolver $resolver
     * @return $this
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'field' => null,
            'propertyPath' => null,
        ])
            ->setAllowedTypes('field', 'string')
            ->setAllowedTypes('propertyPath', ['null', 'string']);

        return $this;
    }
}