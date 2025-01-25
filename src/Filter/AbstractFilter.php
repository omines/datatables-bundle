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
    /**
     * @var array<string, mixed>
     */
    protected array $options = [];

    public function __construct()
    {
        // Initialize the options with the default values set on the OptionsResolver
        $this->set([]);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function set(array $options): static
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        return $this;
    }

    protected function configureOptions(OptionsResolver $resolver): static
    {
        $resolver->setRequired([
            'template_html',
            'template_js',
        ]);

        return $this;
    }

    public function getTemplateHtml(): string
    {
        return $this->options['template_html'];
    }

    public function getTemplateJs(): string
    {
        return $this->options['template_js'];
    }

    abstract public function isValidValue(mixed $value): bool;
}
