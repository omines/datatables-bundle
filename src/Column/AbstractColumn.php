<?php

namespace Omines\DatatablesBundle\Column;

use Doctrine\ORM\Mapping as ORM;
use Omines\DatatablesBundle\Filter\AbstractFilter;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;

abstract class AbstractColumn
{
    /** @var  int
     * @ORM\Column(type="string")
     */
    protected $index;

    /** @var  string */
    protected $name;

    /** @var  string */
    protected $label;

    /** @var  bool */
    protected $visible;

    /** @var  bool */
    protected $searchable;

    /** @var  bool */
    protected $globalSearchable;

    /** @var  string */
    protected $searchValue;

    /** @var  bool */
    protected $orderable;

    /** @var  string */
    protected $orderField;

    /** @var  string */
    protected $orderDirection;

    /** @var  mixed */
    protected $defaultValue;

    /** @var  AbstractFilter */
    protected $filter;

    /** @var  string */
    protected $field;

    /** @var  string */
    protected $propertyPath;

    /** @var  string */
    protected $joinType;

    /** @var  array */
    protected $options;

    /** @var  PropertyAccess */
    private $propertyAccessor;

    /** @var  string */
    private $class;

    /**
     * AbstractColumn constructor.
     */
    public function __construct()
    {
        $this->options = [];
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
    }

    /**
     * @param array $options
     */
    public function set(array $options)
    {
        if (!isset($options['name'])) {
            $options['name'] = "column-{$options['index']}";
        }

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $accessor = PropertyAccess::createPropertyAccessorBuilder()
            ->enableMagicCall()
            ->getPropertyAccessor();

        foreach ($this->options as $setter => $value) {
            $accessor->setValue($this, $setter, $value);
        }
    }

    /**
     * @param OptionsResolver $resolver
     * @return $this
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'index' => null,
            'name' => null,
            'label' => null,
            'visible' => true,
            'orderable' => true,
            'orderField' => null,
            'orderDirection' => null,
            'searchable' => true,
            'searchValue' => null,
            'globalSearchable' => true,
            'defaultValue' => '',
            'filter' => null,
            'joinType' => 'join',
            'class' => null
        ])
            ->setAllowedTypes('index', 'integer')
            ->setAllowedTypes('name', 'string')
            ->setAllowedTypes('label', ['null', 'string'])
            ->setAllowedTypes('visible', 'boolean')
            ->setAllowedTypes('orderable', 'boolean')
            ->setAllowedTypes('orderField', ['null', 'string'])
            ->setAllowedTypes('orderDirection', ['null', 'string'])
            ->setAllowedTypes('searchable', 'boolean')
            ->setAllowedTypes('globalSearchable', 'boolean')
            ->setAllowedTypes('searchValue', ['null', 'string'])
            ->setAllowedTypes('filter', ['null', 'array'])
            ->setAllowedTypes('joinType', ['null', 'string'])
            ->setAllowedTypes('class', ['null', 'string'])
            ->setAllowedValues('orderDirection', function ($value) {
                return $value == null || in_array($value, ['ASC', 'DESC']);
            });

        return $this;
    }

    /**
     * @return int
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param int $index
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     */
    public function setLabel($label)
    {
        $this->label = $label;
    }

    /**
     * @return bool
     */
    public function isVisible()
    {
        return $this->visible;
    }

    /**
     * @param bool $visible
     */
    public function setVisible($visible)
    {
        $this->visible = $visible;
    }

    /**
     * @return bool
     */
    public function isSearchable()
    {
        return $this->searchable;
    }

    /**
     * @param bool $searchable
     */
    public function setSearchable($searchable)
    {
        $this->searchable = $searchable;
    }
    /**
     * @return bool
     */
    public function isOrderable()
    {
        return $this->orderable;
    }

    /**
     * @param bool $orderable
     */
    public function setOrderable($orderable)
    {
        $this->orderable = $orderable;
    }

    /**
     * @return string
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param string $field
     */
    public function setField($field)
    {
        $this->field = $field;
    }

    /**
     * @return string
     */
    public function getPropertyPath()
    {
        return $this->propertyPath;
    }

    /**
     * @param string $propertyPath
     */
    public function setPropertyPath($propertyPath)
    {
        $this->propertyPath = $propertyPath;
    }


    /**
     * @return mixed
     */
    public function getDefaultValue()
    {
        return $this->defaultValue;
    }

    /**
     * @param $defaultValue
     */
    public function setDefaultValue($defaultValue)
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * @return AbstractFilter
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @return string
     */
    public function getSearchValue()
    {
        return $this->searchValue;
    }

    /**
     * @param string $searchValue
     */
    public function setSearchValue($searchValue)
    {
        $this->searchValue = $searchValue;
    }

    /**
     * @return string
     */
    public function getOrderField()
    {
        return $this->orderField;
    }

    /**
     * @param string $orderField
     */
    public function setOrderField($orderField)
    {
        $this->orderField = $orderField;
    }

    /**
     * @return string
     */
    public function getOrderDirection()
    {
        return $this->orderDirection;
    }

    /**
     * @return mixed
     */
    public function getJoinType()
    {
        return $this->joinType;
    }

    /**
     * @param mixed $joinType
     */
    public function setJoinType($joinType)
    {
        $this->joinType = $joinType;
    }

    /**
     * @param string $orderDirection
     */
    public function setOrderDirection($orderDirection)
    {
        $this->orderDirection = $orderDirection;
    }

    /**
     * @return bool
     */
    public function isGlobalSearchable()
    {
        return $this->globalSearchable;
    }

    /**
     * @param bool $globalSearchable
     */
    public function setGlobalSearchable($globalSearchable)
    {
        $this->globalSearchable = $globalSearchable;
    }

    /**
     * @return string
     */
    public function getClass()
    {
        return $this->class;
    }

    /**
     * @param string $class
     */
    public function setClass($class)
    {
        $this->class = $class;
    }

    /**
     * @param array $filterClassAndOptions
     * @throws \Exception
     */
    public function setFilter(array $filterClassAndOptions = null)
    {
        if ($filterClassAndOptions != null) {
            if (!isset($filterClassAndOptions[0]) || !is_string($filterClassAndOptions[0]) && !$filterClassAndOptions[0] instanceof AbstractFilter) {
                throw new \Exception('AbstractColumn::setFilter(): Set a Filter class.');
            }

            if (isset($filterClassAndOptions[1]) && !is_array($filterClassAndOptions[1])) {
                throw new \Exception('AbstractColumn::setFilter(): Set an options array.');
            }

            /** @var AbstractFilter $filter */
            $filter = new $filterClassAndOptions[0];
            $filter->set(isset($filterClassAndOptions[1]) ? $filterClassAndOptions[1] : []);

            $this->filter = $filter;
        }
    }
}