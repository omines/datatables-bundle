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
use Omines\DataTablesBundle\Event\AbstractEvent;
use Omines\DataTablesBundle\Event\Callback;
use Omines\DataTablesBundle\Event\Event;
use Symfony\Component\DependencyInjection\ServiceLocator;
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
    const DEFAULT_SETTINGS = [
        'name' => 'dt',
        'class_name' => 'table table-bordered',
        'column_filter' => null,
        'language_from_cdn' => true,
        'request_state' => null,
        'translation_domain' => 'messages',
    ];

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
        'ajax' => true, //can contain the callback url
        'searchDelay' => 400,
        'dom' => 'lftrip',
        'orderCellsTop' => true,
        'stateSave' => false,
    ];

    const SORT_ASCENDING = 'asc';
    const SORT_DESCENDING = 'desc';

    /** @var ServiceLocator */
    private $adapterLocator;

    /** @var AbstractColumn[] */
    protected $columns = [];

    /** @var array<string, AbstractColumn> */
    protected $columnsByName = [];

    /** @var Callback[] */
    protected $callbacks = [];

    /** @var Event[] */
    protected $events = [];

    /** @var array */
    protected $options;

    /** @var array */
    protected $settings;

    /** @var callable */
    protected $transformer;

    /** @var AdapterInterface */
    protected $adapter;

    /** @var DataTableState */
    private $state;

    /**
     * DataTable constructor.
     *
     * @param array $settings
     * @param array $options
     * @param DataTableState $state
     * @param ServiceLocator $adapterLocator
     */
    public function __construct(array $settings = [], array $options = [], DataTableState $state = null, ServiceLocator $adapterLocator = null)
    {
        $this->state = $state ?? new DataTableState($this);
        $this->adapterLocator = $adapterLocator;

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $resolver = new OptionsResolver();
        $this->configureSettings($resolver);
        $this->settings = $resolver->resolve($settings);

        if (null !== $this->settings['column_filter']) {
            throw new \LogicException("The 'column_filter' setting is currently not supported and must be null");
        }
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
            throw new \RuntimeException(sprintf("There already is a column with name '%s'", $name));
        }

        // TODO: Is index really needed?
        /* @var AbstractColumn $column */
        $this->columns[] = $column = new $type(array_merge(['name' => $name, 'index' => count($this->columns)], $options));
        $this->columnsByName[$column->getName()] = $column;
        $column->setDataTable($this);

        return $this;
    }

    /**
     * @param string $class
     * @param array $options
     * @return $this
     */
    public function on($class, $options = [])
    {
        /** @var AbstractEvent $event */
        $event = new $class();
        $event->set($options);

        switch ($class) {
            case Event::class:
                $this->events[] = $event;
                break;
            case Callback::class:
                $this->callbacks[] = $event;
                break;
            default:
                throw new \LogicException("Class $class is neither an event or a callback");
        }

        return $this;
    }

    /**
     * @return Callback[]
     */
    public function getCallbacks()
    {
        return $this->callbacks;
    }

    /**
     * @param int $index
     * @return AbstractColumn
     */
    public function getColumn(int $index): AbstractColumn
    {
        if ($index < 0 || $index >= count($this->columns)) {
            throw new \InvalidArgumentException(sprintf('There is no column with index %d', $index));
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
            throw new \InvalidArgumentException(sprintf("There is no column named '%s'", $name));
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
     * @return Event[]
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->settings['name'];
    }

    /**
     * @return AdapterInterface
     */
    public function getAdapter(): AdapterInterface
    {
        return $this->adapter;
    }

    /**
     * @param string $adapter
     * @return DataTable
     */
    public function createAdapter(string $adapter, array $options = []): self
    {
        if (null !== $this->adapterLocator && $this->adapterLocator->has($adapter)) {
            return $this->setAdapter($this->adapterLocator->get($adapter), $options);
        } elseif (class_exists($adapter) && in_array(AdapterInterface::class, class_implements($adapter), true)) {
            return $this->setAdapter(new $adapter(), $options);
        } else {
            throw new \InvalidArgumentException(sprintf('Could not resolve adapter type "%s" to a service or class implementing AdapterInterface', $adapter));
        }
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
     * @return DataTableState
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param Request $request
     * @return $this
     */
    public function handleRequest(Request $request)
    {
        $this->state->setDraw($request->query->getInt('draw'));
        $this->state->setFromInitialRequest(0 === $request->query->getInt('draw') && $this->getSetting('requestState') && 1 === $request->get($this->getRequestParam('state', true)));

        if ($this->state->isFromInitialRequest() || $this->state->getDraw() > 0) {
            $this->handleInitialRequest($request);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param bool $prefix
     * @return string
     */
    private function getRequestParam(string $name, bool $prefix = false)
    {
        return $prefix ? "{$this->getSetting('name')}_$name" : $name;
    }

    /**
     * @param Request $request
     */
    private function handleInitialRequest(Request $request)
    {
        $state = $this->getState();
        $isInitial = $state->isFromInitialRequest();
        $search = $request->get($this->getRequestParam('search', $isInitial), []);

        $state->setStart((int) $request->get($this->getRequestParam('start', $isInitial), 0));
        $state->setLength((int) $request->get($this->getRequestParam('length', $isInitial), -1));
        $state->setGlobalSearch($search['value'] ?? '');

        $this->handleOrderBy($request);
        $this->handleSearch($request);
    }

    private function handleOrderBy(Request $request)
    {
        $state = $this->getState()->setOrderBy([]);
        $isInitial = $state->isFromInitialRequest();
        foreach ($request->get($this->getRequestParam('order', $isInitial), []) as $order) {
            $column = $this->getColumn((int) $order['column']);

            if ($column->isOrderable()) {
                $state->addOrderBy($column, $order['dir']);
            }
        }
    }

    private function handleSearch(Request $request)
    {
        $state = $this->getState();
        $isInitial = $state->isFromInitialRequest();
        foreach ($request->get($this->getRequestParam('columns', $isInitial), []) as $key => $search) {
            $column = $this->getColumn((int) $key);
            $value = $this->getState()->isFromInitialRequest() ? $search : $search['search']['value'];

            if ($column->isSearchable() && !empty($value) && null !== $column->getFilter() && $column->getFilter()->isValidValue($value)) {
                $state->setColumnSearch($column, $value);
            }
        }
    }

    /**
     * @return JsonResponse
     */
    public function getResponse()
    {
        $resultSet = $this->getResultSet();

        return new JsonResponse([
            'draw' => $this->getState()->getDraw(),
            'recordsTotal' => $resultSet->getTotalRecords(),
            'recordsFiltered' => $resultSet->getTotalDisplayRecords(),
            'data' => $resultSet->getData(),
        ]);
    }

    /**
     * @return ResultSetInterface
     */
    protected function getResultSet(): ResultSetInterface
    {
        if (null === $this->adapter) {
            throw new \LogicException('No adapter was configured to retrieve data');
        }

        return $this->adapter->getData($this->state);
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    /**
     * @param $name
     * @return mixed|null
     */
    public function getSetting($name)
    {
        return $this->settings[$name] ?? null;
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
    public function getOptions()
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
     * @param int|string|AbstractColumn $column
     * @param string $direction
     * @return self
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
     * @param mixed $context
     * @return self
     */
    public function setContext($context): self
    {
        $this->state->setContext($context);

        return $this;
    }

    /**
     * @param string $name
     * @return self
     */
    public function setName(string $name): self
    {
        if (empty($name)) {
            throw new \InvalidArgumentException('DataTable name cannot be empty');
        }
        $this->settings['name'] = $name;

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
    protected function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults(self::DEFAULT_SETTINGS)
            ->setAllowedTypes('name', 'string')
            ->setAllowedTypes('class_name', 'string')
            ->setAllowedTypes('column_filter', ['null', 'string'])
            ->setAllowedTypes('language_from_cdn', 'bool')
            ->setAllowedTypes('translation_domain', 'string')
        ;

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
