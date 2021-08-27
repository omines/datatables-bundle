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

use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\Exception\MissingDependencyException;
use Symfony\Component\Form\Extension\Core\DataTransformer\MoneyToLocalizedStringTransformer;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * MoneyColumn.
 *
 * @author Max Mishyn <max.mishyn@wisetiger.co.uk>
 */
class MoneyColumn extends AbstractColumn
{
    /**
     * @var MoneyToLocalizedStringTransformer
     */
    private $transformer;

    public function normalize($value): string
    {
        return $this->isRaw() ? (string) $value : $this->format(intval($value));
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'raw' => false,
                'currency' => '',
                'divisor' => 1,
                'scale' => 2,
            ])
            ->setAllowedTypes('raw', 'bool')
            ->setAllowedTypes('currency', 'string')
            ->setAllowedTypes('divisor', 'int')
            ->setAllowedTypes('scale', 'int')
        ;

        return $this;
    }

    public function initialize(string $name, int $index, array $options, DataTable $dataTable)
    {
        parent::initialize($name, $index, $options, $dataTable);
        if (class_exists('Symfony\Component\Form\Extension\Core\DataTransformer\MoneyToLocalizedStringTransformer')) {
            $this->transformer = new MoneyToLocalizedStringTransformer($this->options['scale'], true, \NumberFormatter::ROUND_HALFUP, $this->options['divisor']);
        } else {
            throw new MissingDependencyException('You must have Symfony\Form installed to use ' . self::class);
        }
    }

    public function isRaw(): bool
    {
        return $this->options['raw'];
    }

    public function getCurrency(): ?string
    {
        return $this->options['currency'];
    }

    public function getDivisor(): ?int
    {
        return $this->options['divisor'];
    }

    public function getScale(): ?int
    {
        return $this->options['scale'];
    }

    public function isValidForSearch($value): bool
    {
        return is_numeric($value);
    }

    public function format(int $val): string
    {
        if (empty($this->getCurrency())) {
            return $this->transformer->transform($val);
        }

        $locale = \Locale::getDefault();
        $format = new \NumberFormatter($locale, \NumberFormatter::CURRENCY);
        $pattern = $format->formatCurrency(123, $this->getCurrency());

        preg_match('/^([^\s\xc2\xa0]*)[\s\xc2\xa0]*123(?:[,.]0+)?[\s\xc2\xa0]*([^\s\xc2\xa0]*)$/u', $pattern, $matches);

        if (!empty($matches[1])) {
            $format = $matches[1] . $this->transformer->transform($val);
        } elseif (!empty($matches[2])) {
            $format = $this->transformer->transform($val) . $matches[2];
        } else {
            $format = $this->transformer->transform($val);
        }

        return $format;
    }
}
