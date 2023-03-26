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

use Omines\DataTablesBundle\Exception\InvalidArgumentException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * EnumColumn
 * Find search-Values in Enum::labels() or names of an enum and configure the search-query accordingly.
 *
 * Use:
 * $datatable->add('gender', EnumColumn::class, [
 *       'class' => FooEnum::class,
 * ])
 *
 * Optional: implement label() in your enum.
 */
class EnumColumn extends AbstractColumn
{
    protected function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);
       
        $resolver->setDefault('searchable', true);
        $resolver->setDefault('visible', true);

        $resolver->setDefault('class', null);
        $resolver->setAllowedTypes('class', ['string', 'object', 'mixed']);
        $resolver->setRequired('class');

        $resolver->setDefault('searchIn', function (string $value) {
            return self::search($this->options["class"], $value);
        });

        return $this;
    }

    /**
     * @param class-string $enum
     * @return int[]|string[]
     */
    public static function search(string $enum, string $search): array
    {
        if(!enum_exists($enum))
        {
            throw new InvalidArgumentException(sprintf('%s is not a enum class', $enum));
        }

        /** @var \UnitEnum $enum */
        $items = [];
        foreach($enum::cases() as $case) {

            if(method_exists($enum, 'label'))
            {
                if(str_contains($case->label(), $search)) {
                    $items[] = $case->value;
                }
            }
            else
            {
                if(str_contains($case->name, $search)) {
                    $items[] = $case->value;
                }
            }
        }

        return $items;
    }

    public function normalize(mixed $value): mixed
    {
        if(!$value)
        {return null;}

        if(method_exists($this->options["class"], 'label'))
        {
            return $value->label();
        }

        return $value->name;
    }

    public function isValidForSearch(mixed $value): bool
    {
        return count(self::search($this->options["class"], $value)) > 0;
    }
}
