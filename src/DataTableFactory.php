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
use Omines\DataTablesBundle\Exception\InvalidArgumentException;
use Symfony\Component\HttpFoundation\Request;

class DataTableFactory
{
    /** @var Instantiator */
    protected $instantiator;

    /** @var DataTableRendererInterface */
    protected $renderer;

    /** @var array<string, DataTableTypeInterface> */
    protected $resolvedTypes = [];

    /** @var array */
    protected $config;

    /**
     * DataTableFactory constructor.
     *
     * @param array $config
     * @param DataTableRendererInterface $renderer
     */
    public function __construct(array $config, DataTableRendererInterface $renderer)
    {
        $this->config = $config;
        $this->renderer = $renderer;
    }

    /**
     * @param Instantiator $instantiator
     */
    public function setInstantiator(Instantiator $instantiator)
    {
        $this->instantiator = $instantiator;
    }

    /**
     * @param array $options
     * @return DataTable
     */
    public function create(array $options = [])
    {
        $config = $this->config;

        return (new DataTable(array_merge($config['options'] ?? [], $options), $this->instantiator))
            ->setRenderer($this->renderer)
            ->setMethod($config['method'] ?? Request::METHOD_POST)
            ->setPersistState($config['persist_state'] ?? 'fragment')
            ->setTranslationDomain($config['translation_domain'] ?? 'messages')
            ->setLanguageFromCDN($config['language_from_cdn'] ?? true)
            ->setTemplate($config['template'] ?? DataTable::DEFAULT_TEMPLATE, $config['template_parameters'] ?? [])
        ;
    }

    /**
     * @param string|DataTableTypeInterface $type
     * @param array $typeOptions
     * @param array $options
     * @return DataTable
     */
    public function createFromType($type, array $typeOptions = [], array $options = [])
    {
        $dataTable = $this->create($options);

        if (is_string($type)) {
            $name = $type;
            if (isset($this->resolvedTypes[$name])) {
                $type = $this->resolvedTypes[$name];
            } else {
                $this->resolvedTypes[$name] = $type = $this->resolveType($name);
            }
        }

        $type->configure($dataTable, $typeOptions);

        return $dataTable;
    }

    /**
     * Resolves a dynamic type to an instance via services or instantiation.
     *
     * @param string $type
     * @return DataTableTypeInterface
     */
    private function resolveType(string $type): DataTableTypeInterface
    {
        if (null !== $this->instantiator && $type = $this->instantiator->getType($type)) {
            return $type;
        } elseif (class_exists($type) && in_array(DataTableTypeInterface::class, class_implements($type), true)) {
            return new $type();
        }

        throw new InvalidArgumentException(sprintf('Could not resolve type "%s" to a service or class', $type));
    }
}
