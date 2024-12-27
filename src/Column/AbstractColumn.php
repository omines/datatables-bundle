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
use Omines\DataTablesBundle\DataTableState;
use Omines\DataTablesBundle\Filter\AbstractFilter;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * AbstractColumn.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
abstract class AbstractColumn
{
    /** @var array<string, OptionsResolver> */
    private static array $resolversByClass = [];

    private string $name;
    private int $index;
    private DataTable $dataTable;

    /** @var array<string, mixed> */
    protected array $options;

    /**
     * @param array<string, mixed> $options
     */
    public function initialize(string $name, int $index, array $options, DataTable $dataTable): void
    {
        $this->name = $name;
        $this->index = $index;
        $this->dataTable = $dataTable;

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
     * @param mixed $value The single value of the column, if mapping makes it possible to derive one
     * @param mixed $context All relevant data of the entire row
     */
    public function transform(mixed $value = null, mixed $context = null): mixed
    {
        $data = $this->getData();
        if (is_callable($data)) {
            $value = call_user_func($data, $context, $value, $this);
        } elseif (null === $value) {
            $value = $data;
        }

        return $this->render($this->normalize($value), $context);
    }

    /**
     * Apply final modifications before rendering to result.
     *
     * @param mixed $value The raw data pending rendering
     * @param mixed $context All relevant data of the entire row
     */
    protected function render(mixed $value, mixed $context): mixed
    {
        if (is_string($render = $this->options['render'])) {
            return sprintf($render, $value);
        } elseif (is_callable($render)) {
            return call_user_func($render, $value, $context, $this);
        }

        return $value;
    }

    abstract public function normalize(mixed $value): mixed;

    protected function configureOptions(OptionsResolver $resolver): static
    {
        $resolver
            ->setDefaults([
                'label' => null,
                'data' => null,
                'field' => null,
                'propertyPath' => null,
                'visible' => true,
                'orderable' => null,
                'orderField' => null,
                'searchable' => null,
                'globalSearchable' => null,
                'filter' => null,
                'className' => null,
                'render' => null,
                'leftExpr' => null,
                'operator' => '=',
                'rightExpr' => null,
                'exporterOptions' => [],
            ])
            ->setAllowedTypes('label', ['null', 'string'])
            ->setAllowedTypes('data', ['null', 'string', 'callable'])
            ->setAllowedTypes('field', ['null', 'string'])
            ->setAllowedTypes('propertyPath', ['null', 'string'])
            ->setAllowedTypes('visible', 'boolean')
            ->setAllowedTypes('orderable', ['null', 'boolean'])
            ->setAllowedTypes('orderField', ['null', 'string'])
            ->setAllowedTypes('searchable', ['null', 'boolean'])
            ->setAllowedTypes('globalSearchable', ['null', 'boolean'])
            ->setAllowedTypes('filter', ['null', AbstractFilter::class])
            ->setAllowedTypes('className', ['null', 'string'])
            ->setAllowedTypes('render', ['null', 'string', 'callable'])
            ->setAllowedTypes('operator', ['string'])
            ->setAllowedTypes('leftExpr', ['null', 'string', 'callable'])
            ->setAllowedTypes('rightExpr', ['null', 'string', 'callable'])
            ->setAllowedTypes('exporterOptions', ['array'])
            ->setInfo('exporterOptions', 'Specific exporter options can be specified here, where the key is the exporter name and the value is an array of options.')
        ;

        return $this;
    }

    public function getIndex(): int
    {
        return $this->index;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->options['label'] ?? "{$this->dataTable->getName()}.columns.{$this->getName()}";
    }

    public function getField(): ?string
    {
        return $this->options['field'];
    }

    public function getPropertyPath(): ?string
    {
        return $this->options['propertyPath'];
    }

    public function getData(): callable|string|null
    {
        return $this->options['data'];
    }

    public function isVisible(): bool
    {
        return $this->options['visible'];
    }

    public function isSearchable(): bool
    {
        return $this->options['searchable'] ?? !empty($this->getField());
    }

    public function isOrderable(): bool
    {
        return $this->options['orderable'] ?? !empty($this->getOrderField());
    }

    public function getFilter(): ?AbstractFilter
    {
        return $this->options['filter'] ?? null;
    }

    public function getOrderField(): ?string
    {
        return $this->options['orderField'] ?? $this->getField();
    }

    public function isGlobalSearchable(): bool
    {
        return $this->options['globalSearchable'] ?? $this->isSearchable();
    }

    public function getLeftExpr(): mixed
    {
        $leftExpr = $this->options['leftExpr'];
        if (null === $leftExpr) {
            return $this->getField();
        }
        if (is_callable($leftExpr)) {
            return call_user_func($leftExpr, $this->getField());
        }

        return $leftExpr;
    }

    public function getRightExpr(mixed $value): mixed
    {
        $rightExpr = $this->options['rightExpr'];
        if (null === $rightExpr) {
            return $value;
        }
        if (is_callable($rightExpr)) {
            return call_user_func($rightExpr, $value);
        }

        return $rightExpr;
    }

    public function getOperator(): string
    {
        return $this->options['operator'];
    }

    public function getClassName(): ?string
    {
        return $this->options['className'] ?? null;
    }

    public function getDataTable(): DataTable
    {
        return $this->dataTable;
    }

    public function getState(): DataTableState
    {
        return $this->dataTable->getState();
    }

    public function setOption(string $name, mixed $value): static
    {
        $this->options[$name] = $value;

        return $this;
    }

    public function isValidForSearch(mixed $value): bool
    {
        return true;
    }

    /**
     * @param string $exporterName one of the exporter names as returned by DataTableExporterInterface::getName()
     * @return array<string, mixed>
     */
    public function getExporterOptions(string $exporterName): array
    {
        return $this->options['exporterOptions'][$exporterName] ?? [];
    }
}
