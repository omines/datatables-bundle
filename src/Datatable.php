<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DatatablesBundle;

use Omines\DatatablesBundle\Adapter\AdapterInterface;
use Omines\DatatablesBundle\Column\AbstractColumn;
use Omines\DatatablesBundle\Event\AbstractEvent;
use Omines\DatatablesBundle\Event\Callback;
use Omines\DatatablesBundle\Event\Event;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;

class Datatable
{
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

    /** @var DatatableState */
    private $state;

    /**
     * class constructor.
     *
     * @param array $settings
     * @param array $options
     * @param DatatableState $state
     */
    public function __construct($settings, $options, DatatableState $state = null)
    {
        $this->state = $state ?: new DatatableState();

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
     * @param string $class
     * @param array $options
     * @return $this
     */
    public function column($class, $options = [])
    {
        /** @var AbstractColumn $column */
        $column = new $class();
        $column->set(array_merge(['index' => count($this->state->getColumns())], $options));

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
     * @return Event[]
     */
    public function getEvents()
    {
        return $this->events;
    }

    /**
     * @return AdapterInterface
     */
    public function getAdapter()
    {
        return $this->adapter;
    }

    /**
     * @param AdapterInterface $adapter
     * @return $this
     */
    public function setAdapter(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;

        return $this;
    }

    /**
     * @return DatatableState
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
        $this->state->setStart($request->get($this->getRequestParam('start', $this->state->isFromInitialRequest())));
        $this->state->setLength($request->get($this->getRequestParam('length', $this->state->isFromInitialRequest())));
        $this->state->setSearch($request->get($this->getRequestParam('search', $this->state->isFromInitialRequest())));

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

    public function getData()
    {
        return $this->mapData(false);
    }

    /**
     * @return JsonResponse
     */
    public function getResponse()
    {
        return new JsonResponse($this->mapData(true));
    }

    private function mapData($all = true)
    {
        $this->adapter->handleState($this->state);

        $data = array_map(function ($row) use ($all) {
            $result = $this->adapter->mapRow($this->state->getColumns(), $row, $all);

            if (!is_null($this->rowFormatter)) {
                $result = call_user_func_array($this->rowFormatter, [$result, $row]);
            }

            return $result;
        }, $this->adapter->getData());

        if ($all) {
            return [
                'draw' => $this->getState()->getDraw(),
                'recordsTotal' => $this->adapter->getTotalRecords(),
                'recordsFiltered' => $this->adapter->getTotalDisplayRecords(),
                'data' => $data,
            ];
        } else {
            return $data;
        }
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

    protected function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'name' => 'datatable',
            'class' => 'table table-bordered',
            'languageFromCdn' => true,
            'columnFilter' => null,
            'requestState' => null,
        ])
            ->setAllowedTypes('name', 'string')
            ->setAllowedTypes('class', 'string')
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
        $resolver->setDefaults([
            'jQueryUI' => true,
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
        ]);

        return $this;
    }
}
