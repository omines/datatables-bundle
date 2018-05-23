<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle;

use Omines\DataTablesBundle\Adapter\AdapterInterface;
use Omines\DataTablesBundle\Adapter\ResultSetInterface;
use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\DependencyInjection\Instantiator;
use Omines\DataTablesBundle\Exception\InvalidArgumentException;
use Omines\DataTablesBundle\Exception\InvalidConfigurationException;
use Omines\DataTablesBundle\Exception\InvalidStateException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * DataTable.
 *
 * @author Robbert Beesems <robbert.beesems@omines.com>
 */
class DataTable
{
    const DEFAULT_OPTIONS = [
        'jQueryUI' => false,
        'pagingType' => 'full_numbers',
        'lengthMenu' => [[10, 25, 50, -1], [10, 25, 50, 'All']],
        'pageLength' => 10,
        'displayStart' => 0,
        'serverSide' => true,
        'processing' => true,
        'paging' => true,
        'lengthChange' => true,
        'ordering' => true,
        'searching' => false,
        'search' => null,
        'autoWidth' => false,
        'order' => [],
        'searchDelay' => 400,
        'dom' => 'lftrip',
        'orderCellsTop' => true,
        'stateSave' => false,
        'fixedHeader' => false,
    ];

    const DEFAULT_TEMPLATE = '@DataTables/datatable_html.html.twig';
    const SORT_ASCENDING = 'asc';
    const SORT_DESCENDING = 'desc';

    /** @var AdapterInterface */
    protected $adapter;

    /** @var AbstractColumn[] */
    protected $columns = [];

    /** @var array<string, AbstractColumn> */
    protected $columnsByName = [];

    /** @var string */
    protected $method = Request::METHOD_POST;

    /** @var array */
    protected $options;

    /** @var bool */
    protected $languageFromCDN = true;

    /** @var string */
    protected $name = 'dt';

    /** @var string */
    protected $persistState = 'fragment';

    /** @var string */
    protected $template = self::DEFAULT_TEMPLATE;

    /** @var array */
    protected $templateParams = [];

    /** @var callable */
    protected $transformer;

    /** @var string */
    protected $translationDomain = 'messages';

    /** @var DataTableRendererInterface */
    private $renderer;

    /** @var DataTableState */
    private $state;

    /** @var Instantiator */
    private $instantiator;

    /**
     * DataTable constructor.
     *
     * @param array $options
     * @param Instantiator|null $instantiator
     */
    public function __construct(array $options = [], Instantiator $instantiator = null)
    {
        $this->instantiator = $instantiator ?? new Instantiator();

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    /**
     * @param string $name
     * @param string $type
     * @param array $options
     * @return $this
     */
    public function add(string $name, string $type, array $options = [])
    {
        // Ensure name is unique
        if (isset($this->columnsByName[$name])) {
            throw new InvalidArgumentException(sprintf("There already is a column with name '%s'", $name));
        }

        $column = $this->instantiator->getColumn($type);
        $column->initialize($name, count($this->columns), $options, $this);

        $this->columns[] = $column;
        $this->columnsByName[$name] = $column;

        return $this;
    }

    /**
     * @param int|string|AbstractColumn $column
     * @param string $direction
     * @return $this
     */
    public function addOrderBy($column, string $direction = self::SORT_ASCENDING)
    {
        if (!$column instanceof AbstractColumn) {
            $column = is_int($column) ? $this->getColumn($column) : $this->getColumnByName((string) $column);
        }
        $this->options['order'][] = [$column->getIndex(), $direction];

        return $this;
    }

    /**
     * @param string $adapter
     * @return $this
     */
    public function createAdapter(string $adapter, array $options = []): self
    {
        return $this->setAdapter($this->instantiator->getAdapter($adapter), $options);
    }

    /**
     * @return AdapterInterface
     */
    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * @param int $index
     * @return AbstractColumn
     */
    public function getColumn(int $index): AbstractColumn
    {
        if ($index < 0 || $index >= count($this->columns)) {
            throw new InvalidArgumentException(sprintf('There is no column with index %d', $index));
        }

        return $this->columns[$index];
    }

    /**
     * @param string $name
     * @return AbstractColumn
     */
    public function getColumnByName(string $name): AbstractColumn
    {
        if (!isset($this->columnsByName[$name])) {
            throw new InvalidArgumentException(sprintf("There is no column named '%s'", $name));
        }

        return $this->columnsByName[$name];
    }

    /**
     * @return AbstractColumn[]
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return bool
     */
    public function isLanguageFromCDN(): bool
    {
        return $this->languageFromCDN;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return string
     */
    public function getPersistState(): string
    {
        return $this->persistState;
    }

    /**
     * @return DataTableState|null
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @return string
     */
    public function getTranslationDomain(): string
    {
        return $this->translationDomain;
    }

    /**
     * @return bool
     */
    public function isCallback(): bool
    {
        return (null === $this->state) ? false : $this->state->isCallback();
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function handleRequest(Request $request): self
    {
        switch ($this->getMethod()) {
            case Request::METHOD_GET:
                $parameters = $request->query;
                break;
            case Request::METHOD_POST:
                $parameters = $request->request;
                break;
            default:
                throw new InvalidConfigurationException(sprintf("Unknown request method '%s'", $this->getMethod()));
        }
        if ($this->getName() === $parameters->get('_dt')) {
            if (null === $this->state) {
                $this->state = DataTableState::fromDefaults($this);
            }
            $this->state->applyParameters($parameters);
        }

        return $this;
    }

    /**
     * @return JsonResponse
     */
    public function getResponse(): JsonResponse
    {
        if (null === $this->state) {
            throw new InvalidStateException('The DataTable does not know its state yet, did you call handleRequest?');
        }

        $resultSet = $this->getResultSet();
        $response = [
            'draw' => $this->state->getDraw(),
            'recordsTotal' => $resultSet->getTotalRecords(),
            'recordsFiltered' => $resultSet->getTotalDisplayRecords(),
            'data' => iterator_to_array($resultSet->getData()),
        ];
        if ($this->state->isInitial()) {
            $response['options'] = $this->getInitialResponse();
            $response['template'] = $this->renderer->renderDataTable($this, $this->template, $this->templateParams);
        }

        return JsonResponse::create($response);
    }

    protected function getInitialResponse(): array
    {
        return array_merge($this->getOptions(), [
            'columns' => array_map(
                function (AbstractColumn $column) {
                    return [
                        'data' => $column->getName(),
                        'orderable' => $column->isOrderable(),
                        'searchable' => $column->isSearchable(),
                        'visible' => $column->isVisible(),
                        'className' => $column->getClassName(),
                    ];
                }, $this->getColumns()
            ),
        ]);
    }

    /**
     * @return ResultSetInterface
     */
    protected function getResultSet(): ResultSetInterface
    {
        if (null === $this->adapter) {
            throw new InvalidStateException('No adapter was configured yet to retrieve data with. Call "createAdapter" or "setAdapter" before attempting to return data');
        }

        return $this->adapter->getData($this->state);
    }

    /**
     * @return callable|null
     */
    public function getTransformer()
    {
        return $this->transformer;
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getOption($name)
    {
        return $this->options[$name] ?? null;
    }

    /**
     * @param AdapterInterface $adapter
     * @param array|null $options
     * @return DataTable
     */
    public function setAdapter(AdapterInterface $adapter, array $options = null): self
    {
        if (null !== $options) {
            $adapter->configure($options);
        }
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * @param bool $languageFromCDN
     * @return $this
     */
    public function setLanguageFromCDN(bool $languageFromCDN): self
    {
        $this->languageFromCDN = $languageFromCDN;

        return $this;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setMethod(string $method): self
    {
        $this->method = $method;

        return $this;
    }

    /**
     * @param string $persistState
     * @return $this
     */
    public function setPersistState(string $persistState): self
    {
        $this->persistState = $persistState;

        return $this;
    }

    /**
     * @param DataTableRendererInterface $renderer
     * @return $this
     */
    public function setRenderer(DataTableRendererInterface $renderer): self
    {
        $this->renderer = $renderer;

        return $this;
    }

    /**
     * @param string $name
     * @return $this
     */
    public function setName(string $name): self
    {
        if (empty($name)) {
            throw new InvalidArgumentException('DataTable name cannot be empty');
        }
        $this->name = $name;

        return $this;
    }

    /**
     * @param string $template
     * @return $this
     */
    public function setTemplate(string $template, array $parameters = []): self
    {
        $this->template = $template;
        $this->templateParams = $parameters;

        return $this;
    }

    /**
     * @param string $translationDomain
     * @return $this
     */
    public function setTranslationDomain(string $translationDomain): self
    {
        $this->translationDomain = $translationDomain;

        return $this;
    }

    /**
     * @param callable $formatter
     * @return $this
     */
    public function setTransformer(callable $formatter)
    {
        $this->transformer = $formatter;

        return $this;
    }

    /**
     * @param OptionsResolver $resolver
     * @return $this
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(self::DEFAULT_OPTIONS);

        return $this;
    }
}
