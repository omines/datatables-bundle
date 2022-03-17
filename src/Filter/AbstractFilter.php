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

abstract class AbstractFilter
{
    protected string $template_html;

    protected string $template_js;

    protected string $operator;

    public function set(array $options): void
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        foreach ($resolver->resolve($options) as $key => $value) {
            $this->$key = $value;
        }
    }

    protected function configureOptions(OptionsResolver $resolver): static
    {
        $resolver->setDefaults([
            'template_html' => null,
            'template_js' => null,
            'operator' => 'CONTAINS',
        ]);

        return $this;
    }

    public function getTemplateHtml(): string
    {
        return $this->template_html;
    }

    public function getTemplateJs(): string
    {
        return $this->template_js;
    }

    public function getOperator(): string
    {
        return $this->operator;
    }

    abstract public function isValidValue(mixed $value): bool;
}
