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
 * Optional: implement label() in your enum. Or use custom function name.
 */
class EnumColumn extends AbstractColumn
{
    protected function configureOptions(OptionsResolver $resolver): static
    {
        parent::configureOptions($resolver);
       
        $resolver->setDefault('searchable', true);
        $resolver->setDefault('visible', true);

        $resolver->setDefault('class', null);
        $resolver->setAllowedTypes('class', 'string');
        $resolver->setRequired('class');

        $resolver->setDefault('classFunctionRender', 'label');
        $resolver->setAllowedTypes('classFunctionRender', ['string', null]);

        $resolver->setDefault('classFunctionSearch', 'label');
        $resolver->setAllowedTypes('classFunctionSearch', ['string', null]);

        $resolver->setDefault('searchIn', function ($options, string $value) {
            return $this->search($options["class"], $options["classFunctionSearch"], $value);
        });

        return $this;
    }

    /**
     * Case-Insensitive Search
     * @param class-string $enum
     * @return int[]|string[]
     */
    public function search(string $enum, string $classFunctionSearch, string $search): array
    {
        if(!enum_exists($enum))
        {
            throw new InvalidArgumentException(sprintf('%s is not a enum class', $enum));
        }

        /** @var \UnitEnum $enum */
        $items = [];
        foreach($enum::cases() as $case) {

            if($classFunctionSearch && method_exists($enum, $classFunctionSearch))
            {
                if(str_contains(mb_strtolower($case->{$classFunctionSearch}()), mb_strtolower($search))) {
                    $items[] = $case->value;
                }
            }
            else
            {
                if(str_contains(mb_strtolower($case->name), mb_strtolower($search))) {
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

        if(method_exists($this->options["class"], $this->options["classFunctionRender"]))
        {
            return $value->{$this->options["classFunctionRender"]}();
        }

        return $value->name;
    }

    public function isValidForSearch(mixed $value): bool
    {
        return count($this->search($this->options["class"], $this->options["classFunctionSearch"], $value)) > 0;
    }
}
