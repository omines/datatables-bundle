<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Filter;

use Symfony\Component\OptionsResolver\OptionsResolver;

class TextFilter extends AbstractFilter
{
    protected function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'template_html' => '@DataTables/Filter/text.html.twig',
                'template_js' => '@DataTables/Filter/text.js.twig',
                'placeholder' => null,
            ])
            ->setAllowedTypes('placeholder', ['null', 'string']);

        return $this;
    }

    public function getPlaceholder(): ?string
    {
        return $this->options['placeholder'];
    }

    public function isValidValue(mixed $value): bool
    {
        return true;
    }
}
