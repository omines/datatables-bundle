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

class ChoiceFilter extends AbstractFilter
{
    protected ?string $placeholder = null;

    protected array $choices = [];

    protected function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'template_html' => '@DataTables/Filter/select.html.twig',
                'template_js' => '@DataTables/Filter/select.js.twig',
                'placeholder' => null,
                'choices' => [],
            ])
            ->setAllowedTypes('placeholder', ['null', 'string'])
            ->setAllowedTypes('choices', ['array']);

        return $this;
    }

    public function getPlaceholder(): ?string
    {
        return $this->placeholder;
    }

    public function getChoices(): array
    {
        return $this->choices;
    }

    public function isValidValue(mixed $value): bool
    {
        return array_key_exists($value, $this->choices);
    }
}
