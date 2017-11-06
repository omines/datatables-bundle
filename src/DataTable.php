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
        'className' => 'table table-bordered',
        'languageFromCdn' => true,
        'columnFilter' => null,
        'requestState' => null,
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

    /** @var array<string, AbstractColumn> */
    protected $columns = [];

    /** @var Callback[] */
    protected $callbacks;

    /** @var Event[] */
    protected $events;

    /** @var array */
    protected $options;

    /** @var array */
    protected $settings;

    /** @var \Closure */
    protected $rowFormatter;

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

        $this->events = [];
        $this->callbacks = [];

        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $this->options = $resolver->resolve($options);

        $resolver = new OptionsResolver();
        $this->configureSettings($resolver);
        $this->settings = $resolver->resolve($settings);
    }

    /**
     * @param string $name
     * @param string $type
     * @param array $options
     * @return $this
     */
    public function add(string $name, string $type, array $options = [])
    {
        // TODO: Make this a ton more intelligent
        $this->columns[] = $column = new $type(array_merge(['name' => $name, 'index' => count($this->columns)], $options));

        $this->state->addColumn($column);

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
     * @param \Closure $formatter
     * @return $this
     */
    public function format(\Closure $formatter)
    {
        $this->rowFormatter = $formatter;

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
     * @param $start
     * @return $this
     */
    public function setStart($start)
    {
        $this->state->setStart($start);

        return $this;
    }

    /**
     * @param $length
     * @return $this
     */
    public function setLength($length)
    {
        $this->state->setLength($length);

        return $this;
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

    private function getRequestParam($name, $prefix)
    {
        if ($prefix) {
            return "{$this->getSetting('name')}_$name";
        } else {
            return $name;
        }
    }

    private function handleInitialRequest(Request $request)
    {
        $this->state->setStart($request->get($this->getRequestParam('start', $this->state->isFromInitialRequest())) ?? 0);
        $this->state->setLength($request->get($this->getRequestParam('length', $this->state->isFromInitialRequest())) ?? -1);
        $this->state->setSearch($request->get($this->getRequestParam('search', $this->state->isFromInitialRequest())) ?? '');

        foreach ($request->get($this->getRequestParam('order', $this->state->isFromInitialRequest()), []) as $order) {
            $column = $this->getState()->getColumn($order['column']);

            if ($column->isOrderable()) {
                $column->setOrderDirection($order['dir']);
            }
        }

        foreach ($request->get($this->getRequestParam('columns', $this->state->isFromInitialRequest()), []) as $key => $search) {
            $column = $this->getState()->getColumn($key);
            $value = $this->getState()->isFromInitialRequest() ? $search : $search['search']['value'];

            if ('' !== $value && $column->isSearchable() && null !== $column->getFilter() && $column->getFilter()->isValidValue($value)) {
                $column->setSearchValue($value);
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
     * @param int|string $column
     * @param string $direction
     * @return self
     */
    public function setDefaultSort($column, string $direction = self::SORT_ASCENDING)
    {
        @trigger_error('setDefaultSort not implemented yet', E_USER_WARNING);

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
     * @param OptionsResolver $resolver
     * @return $this
     */
    protected function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults(self::DEFAULT_SETTINGS)
            ->setAllowedTypes('name', 'string')
            ->setAllowedTypes('className', 'string')
            ->setAllowedTypes('languageFromCdn', 'bool')
            ->setAllowedTypes('columnFilter', ['null', 'string']);

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
