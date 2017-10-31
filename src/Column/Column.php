<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DataTablesBundle\Column;

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
