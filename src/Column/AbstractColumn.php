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

use Omines\DataTablesBundle\Filter\AbstractFilter;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * AbstractColumn.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
abstract class AbstractColumn
{
    /** @var array<string, OptionsResolver> */
    private static $resolversByClass = [];

    /** @var array<string, mixed> */
    protected $options;

    /**
     * AbstractColumn constructor.
     */
    public function __construct(array $options = [])
    {
        $class = get_class($this);
        if (!isset(self::$resolversByClass[$class])) {
            self::$resolversByClass[$class] = new OptionsResolver();
            $this->configureOptions(self::$resolversByClass[$class]);
        }
        $this->options = self::$resolversByClass[$class]->resolve($options);
    }

    /**
     * The transform function is responsible for converting column-appropriate input to a datatables-usable type.
     *
     * @param mixed $context All relevant data of the entire row
     * @param mixed|null $value The single value of the column, if mapping makes it possible to derive one
     * @return mixed
     */
    public function transform($context, $value = null)
    {
        $data = $this->options['data'];
        if (is_callable($data)) {
            $value = call_user_func($data, $context, $value);
        } elseif (empty($value)) {
            $value = $data;
        }

        return $this->normalize($value);
    }

    /**
     * The normalize function is responsible for converting parsed and processed data to a datatables-usable type.
     *
     * @param mixed $value The single value of the column
     * @return mixed
     */
    abstract public function normalize($value);

    /**
     * @param OptionsResolver $resolver
     * @return $this
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'index' => 1,
                'name' => function (Options $options) { return "column-{$options['index']}"; },
                'label' => function (Options $options) { return $options['field']; },
                'data' => null,
                'field' => null,
                'propertyPath' => null,
                'visible' => true,
                'orderable' => true,
                'orderField' => function (Options $options) { return $options['field']; },
                'searchable' => true,
                'searchValue' => null,
                'globalSearchable' => true,
                'filter' => null,
                'joinType' => 'join',
                'className' => null,
            ])
            ->setRequired([
                'index',
                'name',
            ])
            ->setAllowedTypes('index', 'integer')
            ->setAllowedTypes('name', 'string')
            ->setAllowedTypes('label', ['null', 'string'])
            ->setAllowedTypes('data', ['null', 'string', 'callable'])
            ->setAllowedTypes('field', ['null', 'string'])
            ->setAllowedTypes('propertyPath', ['null', 'string'])
            ->setAllowedTypes('visible', 'boolean')
            ->setAllowedTypes('orderable', 'boolean')
            ->setAllowedTypes('orderField', ['null', 'string'])
            ->setAllowedTypes('searchable', 'boolean')
            ->setAllowedTypes('globalSearchable', 'boolean')
            ->setAllowedTypes('searchValue', ['null', 'string'])
            ->setAllowedTypes('filter', ['null', 'array'])
            ->setAllowedTypes('joinType', ['null', 'string'])
            ->setAllowedTypes('className', ['null', 'string']);

        return $this;
    }

    /**
     * @return int
     */
    public function getIndex(): int
    {
        return $this->options['index'];
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->options['name'];
    }

    /**
     * @return string|null
     */
    public function getLabel()
    {
        return $this->options['label'];
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->options['field'];
    }

    /**
     * @return string
     */
    public function getPropertyPath()
    {
        return $this->options['propertyPath'];
    }

    /**
     * @return callable|string|null
     */
    public function getData()
    {
        return $this->options['data'];
    }

    /**
     * @return bool
     */
    public function isVisible(): bool
    {
        return $this->options['visible'];
    }

    /**
     * @return bool
     */
    public function isSearchable(): bool
    {
        return $this->options['searchable'];
    }

    /**
     * @return bool
     */
    public function isOrderable(): bool
    {
        return $this->options['orderable'];
    }

    /**
     * @return AbstractFilter
     *
     * @todo Is this return hint correct?
     */
    public function getFilter()
    {
        return $this->options['filter'];
    }

    /**
     * @return string|null
     */
    public function getSearchValue()
    {
        return $this->options['searchValue'];
    }

    /**
     * @return string|null
     */
    public function getOrderField()
    {
        return $this->options['orderField'];
    }

    /**
     * @return string|null
     */
    public function getJoinType()
    {
        return $this->options['joinType'];
    }

    /**
     * @return bool
     */
    public function isGlobalSearchable(): bool
    {
        return $this->options['globalSearchable'];
    }

    /**
     * @return string
     */
    public function getClassName()
    {
        return $this->options['className'];
    }

    /**
     * @param array $filterClassAndOptions
     * @throws \Exception
     */
    public function setFilter(array $filterClassAndOptions = null)
    {
        if (null !== $filterClassAndOptions) {
            if (!isset($filterClassAndOptions[0]) || !is_string($filterClassAndOptions[0]) && !$filterClassAndOptions[0] instanceof AbstractFilter) {
                throw new \Exception('AbstractColumn::setFilter(): Set a Filter class.');
            }

            if (isset($filterClassAndOptions[1]) && !is_array($filterClassAndOptions[1])) {
                throw new \Exception('AbstractColumn::setFilter(): Set an options array.');
            }

            /** @var AbstractFilter $filter */
            $filter = new $filterClassAndOptions[0]();
            $filter->set(isset($filterClassAndOptions[1]) ? $filterClassAndOptions[1] : []);

            $this->filter = $filter;
        }
        throw new \LogicException('Is this being used?');
    }

    /**
     * @param string $path
     * @return $this
     */
    public function setPropertyPath(string $path)
    {
        $this->options['propertyPath'] = $path;

        return $this;
    }
}
