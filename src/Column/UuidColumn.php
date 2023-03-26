<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Column;

use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * We need to cast the uuid to string for the search
 */
class UuidColumn extends TextColumn
{
    /**
     * {@inheritdoc}
     */
    protected function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);
       
        $resolver->setDefault('searchable', true);
        $resolver->setDefault('visible', false);

        $resolver->setDefault('leftExpr', function(string $value) { return 'TO_LOWER(CAST('.$value.' as varchar))'; });
        $resolver->setDefault('operator','LIKE');
        $resolver->setDefault('rightExpr', function (string $value) { return '%'.mb_strtolower($value).'%'; });

        return $this;
    }

}
