<?php

/*
 * DataTables Bundle
 * (c) 2017 Omines Internetbureau B.V. - https://omines.nl
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * DateTimeColumn
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class DateTimeColumn extends AbstractColumn
{
    /**
     * @inheritDoc
     */
    public function normalize($value)
    {
        if (null === $value) {
            return $this->getDefaultValue();
        } elseif (!$value instanceof \DateTimeInterface) {
            $value = new \DateTime($value);
        }
        return $value->format($this->options['format']);
    }

    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'format' => 'c',
            ])
            ->setAllowedTypes('format', 'string')
        ;

        return $this;
    }
}