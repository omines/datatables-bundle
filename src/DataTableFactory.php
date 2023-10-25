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

use Omines\DataTablesBundle\DependencyInjection\Instantiator;
use Omines\DataTablesBundle\Exporter\DataTableExporterManager;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;

class DataTableFactory
{
    /** @var array<string, DataTableTypeInterface> */
    protected array $resolvedTypes = [];

    /** @var array<string, mixed> */
    protected array $config;

    /**
     * @param array<string, mixed> $config
     */
    public function __construct(
        array $config,
        protected readonly DataTableRendererInterface $renderer,
        protected readonly Instantiator $instantiator,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly DataTableExporterManager $exporterManager,
    ) {
        $this->config = $config;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function create(array $options = []): DataTable
    {
        $config = $this->config;

        return (new DataTable($this->eventDispatcher, $this->exporterManager, array_merge($config['options'] ?? [], $options), $this->instantiator))
            ->setRenderer($this->renderer)
            ->setMethod($config['method'] ?? Request::METHOD_POST)
            ->setPersistState($config['persist_state'] ?? 'fragment')
            ->setTranslationDomain($config['translation_domain'] ?? 'messages')
            ->setLanguageFromCDN($config['language_from_cdn'] ?? true)
            ->setTemplate($config['template'] ?? DataTable::DEFAULT_TEMPLATE, $config['template_parameters'] ?? [])
        ;
    }

    /**
     * @param array<string, mixed> $typeOptions
     * @param array<string, mixed> $options
     */
    public function createFromType(DataTableTypeInterface|string $type, array $typeOptions = [], array $options = []): DataTable
    {
        $dataTable = $this->create($options);

        if (is_string($type)) {
            $name = $type;
            if (isset($this->resolvedTypes[$name])) {
                $type = $this->resolvedTypes[$name];
            } else {
                $this->resolvedTypes[$name] = $type = $this->instantiator->getType($name);
            }
        }

        $type->configure($dataTable, $typeOptions);

        return $dataTable;
    }
}
