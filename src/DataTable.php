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
use Omines\DataTablesBundle\Exporter\DataTableExporterManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * DataTable.
 *
 * @author Robbert Beesems <robbert.beesems@omines.com>
 */
class DataTable
{
    public const DEFAULT_OPTIONS = [
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

    public const DEFAULT_TEMPLATE = '@DataTables/datatable_html.html.twig';
    public const SORT_ASCENDING = 'asc';
    public const SORT_DESCENDING = 'desc';

    protected ?AdapterInterface $adapter = null;

    /** @var AbstractColumn[] */
    protected array $columns = [];

    /** @var array<string, AbstractColumn> */
    protected array $columnsByName = [];
    protected EventDispatcherInterface $eventDispatcher;
    protected DataTableExporterManager $exporterManager;
    protected string $method = Request::METHOD_POST;

    /** @var array<string, mixed> */
    protected array $options;
    protected bool $languageFromCDN = true;
    protected string $name = 'dt';
    protected string $persistState = 'fragment';
    protected string $template = self::DEFAULT_TEMPLATE;

    /** @var array<string, mixed> */
    protected array $templateParams = [];

    /** @var callable */
    protected $transformer;

    protected string $translationDomain = 'messages';

    private DataTableRendererInterface $renderer;
    private ?DataTableState $state = null;
    private Instantiator $instantiator;

    /**
     * @param array<string, mixed> $options
     */
    public function __construct(EventDispatcherInterface $eventDispatcher, DataTableExporterManager $exporterManager, array $options = [], Instantiator $instantiator = null)
    {
        $this->eventDispatcher = $eventDispatcher;
        $this->exporterManager = $exporterManager;

        $this->instantiator = $instantiator ?? new Instantiator();

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);
    }

    /**
     * @param array<string, mixed> $options
     */
    public function add(string $name, string $type, array $options = []): static
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
     * Adds an event listener to an event on this DataTable.
     *
     * @param string   $eventName The name of the event to listen to
     * @param callable $listener  The listener to execute
     * @param int      $priority  The priority of the listener. Listeners
     *                            with a higher priority are called before
     *                            listeners with a lower priority.
     *
     * @return $this
     */
    public function addEventListener(string $eventName, callable $listener, int $priority = 0): static
    {
        $this->eventDispatcher->addListener($eventName, $listener, $priority);

        return $this;
    }

    /**
     * @param int|string|AbstractColumn $column
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
     * @param array<string, mixed> $options
     */
    public function createAdapter(string $adapter, array $options = []): static
    {
        return $this->setAdapter($this->instantiator->getAdapter($adapter), $options);
    }

    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    public function getColumn(int $index): AbstractColumn
    {
        if ($index < 0 || $index >= count($this->columns)) {
            throw new InvalidArgumentException(sprintf('There is no column with index %d', $index));
        }

        return $this->columns[$index];
    }

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

    public function getEventDispatcher(): EventDispatcherInterface
    {
        return $this->eventDispatcher;
    }

    public function isLanguageFromCDN(): bool
    {
        return $this->languageFromCDN;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getPersistState(): string
    {
        return $this->persistState;
    }

    public function getState(): ?DataTableState
    {
        return $this->state;
    }

    public function getTranslationDomain(): string
    {
        return $this->translationDomain;
    }

    public function isCallback(): bool
    {
        return (null === $this->state) ? false : $this->state->isCallback();
    }

    public function handleRequest(Request $request): static
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

    public function getResponse(): Response
    {
        if (null === $this->state) {
            throw new InvalidStateException('The DataTable does not know its state yet, did you call handleRequest?');
        }

        // Server side export
        if (null !== $this->state->getExporterName()) {
            return $this->exporterManager
                ->setDataTable($this)
                ->setExporterName($this->state->getExporterName())
                ->getResponse();
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

        return new JsonResponse($response);
    }

    /**
     * @return array<string, mixed>
     */
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

    protected function getResultSet(): ResultSetInterface
    {
        if (null === $this->adapter) {
            throw new InvalidStateException('No adapter was configured yet to retrieve data with. Call "createAdapter" or "setAdapter" before attempting to return data');
        }

        return $this->adapter->getData($this->state);
    }

    public function getTransformer(): ?callable
    {
        return $this->transformer;
    }

    /**
     * @return array<string, mixed>
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    public function getOption(string $name): mixed
    {
        return $this->options[$name] ?? null;
    }

    /**
     * @param ?array<string, mixed> $options
     */
    public function setAdapter(AdapterInterface $adapter, array $options = null): static
    {
        if (null !== $options) {
            $adapter->configure($options);
        }
        $this->adapter = $adapter;

        return $this;
    }

    public function setLanguageFromCDN(bool $languageFromCDN): static
    {
        $this->languageFromCDN = $languageFromCDN;

        return $this;
    }

    public function setMethod(string $method): static
    {
        $this->method = $method;

        return $this;
    }

    public function setPersistState(string $persistState): static
    {
        $this->persistState = $persistState;

        return $this;
    }

    public function setRenderer(DataTableRendererInterface $renderer): static
    {
        $this->renderer = $renderer;

        return $this;
    }

    public function setName(string $name): static
    {
        if (empty($name)) {
            throw new InvalidArgumentException('DataTable name cannot be empty');
        }
        $this->name = $name;

        return $this;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function setTemplate(string $template, array $parameters = []): static
    {
        $this->template = $template;
        $this->templateParams = $parameters;

        return $this;
    }

    public function setTranslationDomain(string $translationDomain): static
    {
        $this->translationDomain = $translationDomain;

        return $this;
    }

    /**
     * @return $this
     */
    public function setTransformer(callable $formatter)
    {
        $this->transformer = $formatter;

        return $this;
    }

    /**
     * @return $this
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(self::DEFAULT_OPTIONS);

        return $this;
    }
}
