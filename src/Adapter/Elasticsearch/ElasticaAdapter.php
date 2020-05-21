<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Adapter\Elasticsearch;

use Omines\DataTablesBundle\Adapter\AbstractAdapter;
use Omines\DataTablesBundle\Adapter\AdapterQuery;
use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\DataTableState;
use Omines\DataTablesBundle\Exception\MissingDependencyException;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * ElasticaAdapter.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class ElasticaAdapter extends AbstractAdapter
{
    /** @var array */
    private $clientSettings = [];

    /** @var array */
    private $indices = [];

    /**
     * {@inheritdoc}
     */
    public function configure(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $this->clientSettings = $options['client'];
        $this->indices = (array) $options['index'];
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareQuery(AdapterQuery $query)
    {
        if (!class_exists(\Elastica\Client::class)) {
            throw new MissingDependencyException('Install ruflin/elastica to use the ElasticaAdapter');
        }
        $query->set('client', new \Elastica\Client($this->clientSettings));

        foreach ($query->getState()->getDataTable()->getColumns() as $column) {
            if (null === $column->getField()) {
                $column->setOption('field', $column->getName());
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function mapPropertyPath(AdapterQuery $query, AbstractColumn $column)
    {
        return "[{$column->getField()}]";
    }

    /**
     * {@inheritdoc}
     */
    protected function getResults(AdapterQuery $query): \Traversable
    {
        $state = $query->getState();
        $search = new \Elastica\Search($query->get('client'));
        $search->addIndices($this->indices);

        $q = $this->buildQuery($state);
        if ($state->getLength() > 0) {
            $q->setFrom($state->getStart())->setSize($state->getLength());
        }
        $this->applyOrdering($q, $state);

        $resultSet = $search->search($q);
        $query->setTotalRows($resultSet->getTotalHits());
        $query->setFilteredRows($search->count());

        foreach ($resultSet->getResults() as $result) {
            yield $result->getData();
        }
    }

    protected function buildQuery(DataTableState $state): \Elastica\Query
    {
        $q = new \Elastica\Query();
        if (!empty($globalSearch = $state->getGlobalSearch())) {
            $fields = [];
            foreach ($state->getDataTable()->getColumns() as $column) {
                if ($column->isGlobalSearchable()) {
                    $fields[] = $column->getField();
                }
            }
            $multimatch = (new \Elastica\Query\MultiMatch())
                ->setQuery($globalSearch)
                ->setFields($fields)
            ;
            $q->setQuery($multimatch);
        }

        return $q;
    }

    protected function applyOrdering(\Elastica\Query $query, DataTableState $state)
    {
        foreach ($state->getOrderBy() as list($column, $direction)) {
            /** @var AbstractColumn $column */
            if ($column->isOrderable() && $orderField = $column->getOrderField()) {
                $query->addSort([$orderField => ['order' => $direction]]);
            }
        }
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'client' => [],
                'index' => [],
            ])
            ->setRequired(['client', 'index'])
            ->setAllowedTypes('client', 'array')
            ->setAllowedTypes('index', ['string', 'array'])
        ;
    }
}
