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
    /** @var AbstractColumn[] */
    protected $columns;

    /** @var Callback[] */
    protected $callbacks;

    /** @var Event[] */
    protected $events;

    /** @var \Closure */
    protected $filter;

    /** @var array */
    protected $options;

    /** @var array */
    protected $settings;

    /** @var \Closure */
    protected $rowFormatter;

    /** @var AdapterInterface */
    protected $adapter;

    /** @var int */
    private $draw;

    /**
     * class constructor.
     *
     * @param array $settings
     * @param array $options
     */
    public function __construct($settings, $options)
    {
        $this->columns = [];
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
        $column->set(array_merge(['index' => count($this->columns)], $options));

        $this->columns[] = $column;

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
     * @return AbstractColumn[]
     */
    public function getColumns()
    {
        return $this->columns;
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

    public function handleRequest(Request $request)
    {
        $this->draw = $request->query->getInt('draw');
        $start = (int) $request->get('start');
        $length = (int) $request->get('length', 0);
        $order = $request->get('order', []);
        $search = $request->get('search');
        $columns = $request->get('columns', []);

        $orders = array_map(function ($ele) {
            return (int) $ele['column'];
        }, $order);

        foreach ($this->columns as $key => $column) {
            if (false !== ($c = array_search($key, $orders, true))) {
                if ($column->isOrderable()) {
                    $column->setOrderDirection($order[$c]['dir'] == 'asc' ? 'ASC' : 'DESC');
                } else {
                    throw new \LogicException('Column can not be ordered');
                }
            }

            if (mb_strlen($columns[$key]['search']['value']) > 0 && $column->isSearchable() && $column->getFilter()->isValidValue($columns[$key]['search']['value'])) {
                $column->setSearchValue($columns[$key]['search']['value']);
            }
        }

        $this->adapter->handleRequest(new DatatableState($start, $length, $this->columns, 0 == mb_strlen($search['value']) ? null : $search['value']));

        return $this;
    }

    /**
     * @return JsonResponse
     */
    public function getResponse()
    {
        $data = array_map(function ($row) {
            $result = $this->adapter->mapRow($row);

            if (!is_null($this->rowFormatter)) {
                $result = call_user_func_array($this->rowFormatter, [$result, $row]);
            }

            return $result;
        }, $this->adapter->getData());

        $output = [
            'draw' => $this->draw,
            'recordsTotal' => $this->adapter->getTotalRecords(),
            'recordsFiltered' => $this->adapter->getTotalDisplayRecords(),
            'data' => $data,
        ];

        return new JsonResponse($output);
    }

    /**
     * @return array
     */
    public function getSettings()
    {
        return $this->settings;
    }

    public function getSetting($name)
    {
        return $this->settings[$name];
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    public function getOption($name)
    {
        return $this->options[$name];
    }

    protected function configureSettings(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'name' => 'datatable-' . rand(0, 100),
            'class' => 'table table-bordered',
            'language_from_cdn' => true,
            'column_filter' => null,
        ])
            ->setAllowedTypes('name', 'string')
            ->setAllowedTypes('class', 'string')
            ->setAllowedTypes('language_from_cdn', 'bool')
            ->setAllowedTypes('column_filter', ['null', 'string']);

        return $this;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'jQueryUI' => true,
            'pagingType' => 'full_numbers',
            'lengthMenu' => [[10, 25, 50, -1], [10, 25, 50, 'All']],
            'pageLength' => 10,
            'serverSide' => true,
            'processing' => true,
            'paging' => true,
            'lengthChange' => true,
            'ordering' => true,
            'searching' => false,
            'autoWidth' => false,
            'order' => [],
            'ajax' => true, //can contain the callback url
            'searchDelay' => 400,
            'dom' => 'lftrip',
            'orderCellsTop' => true,
        ]);

        return $this;
    }
}
