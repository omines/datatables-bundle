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
 * DateTimeColumn.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class DateTimeColumn extends AbstractColumn
{
    public function normalize(mixed $value): mixed
    {
        if (null === $value) {
            return $this->options['nullValue'];
        }

        $this->normalizeTimezones();
        assert($this->options['modelTimezone'] instanceof \DateTimeZone);
        assert($this->options['viewTimezone'] instanceof \DateTimeZone);

        if (!$value instanceof \DateTimeInterface) {
            if (!empty($this->options['createFromFormat'])) {
                $value = \DateTime::createFromFormat($this->options['createFromFormat'], (string) $value, $this->options['modelTimezone']);
                if (false === $value) {
                    $errors = \DateTime::getLastErrors();
                    throw new \RuntimeException($errors ? implode(', ', $errors['errors'] ?: $errors['warnings']) : 'DateTime conversion failed for unknown reasons');
                }
            } else {
                $value = new \DateTime((string) $value);
            }
        }

        if ($this->options['modelTimezone'] !== $this->options['viewTimezone']) {
            $value = $value->setTimezone($this->options['viewTimezone']);       // Assignment is required, without it the timezone changes but the times stay the same
        }

        return $value->format($this->options['format']);
    }

    protected function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);

        $resolver
            ->setDefaults([
                'createFromFormat' => '',
                'format' => 'c',
                'nullValue' => '',
                'modelTimezone' => null,
                'viewTimezone' => null,
            ])
            ->setAllowedTypes('createFromFormat', 'string')
            ->setAllowedTypes('format', 'string')
            ->setAllowedTypes('nullValue', 'string')
            ->setAllowedTypes('modelTimezone', ['null', 'string', \DateTimeZone::class])
            ->setAllowedTypes('viewTimezone', ['null', 'string', \DateTimeZone::class])
        ;

        return $this;
    }

    protected function normalizeTimezones(): void
    {
        if (null === $this->options['modelTimezone']) {
            $this->options['modelTimezone'] = date_default_timezone_get();
        }
        if (null === $this->options['viewTimezone']) {
            $this->options['viewTimezone'] = date_default_timezone_get();
        }

        if (is_string($this->options['modelTimezone'])) {
            $this->options['modelTimezone'] = new \DateTimeZone($this->options['modelTimezone']);
        }
        if (is_string($this->options['viewTimezone'])) {
            $this->options['viewTimezone'] = new \DateTimeZone($this->options['viewTimezone']);
        }
    }
}
