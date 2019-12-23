<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Adapter\MongoDB;

use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Model\BSONDocument;
use Omines\DataTablesBundle\Adapter\AbstractAdapter;
use Omines\DataTablesBundle\Adapter\AdapterQuery;
use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableState;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * MongoDBAdapter.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class MongoDBAdapter extends AbstractAdapter
{
    const SORT_MAP = [
        DataTable::SORT_ASCENDING => 1,
        DataTable::SORT_DESCENDING => -1,
    ];

    /** @var Collection */
    private $collection;

    /** @var array */
    private $filters;

    /**
     * {@inheritdoc}
     */
    public function configure(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $this->collection = $options['collection'];
        $this->filters = $options['filters'];
    }

    /**
     * {@inheritdoc}
     */
    protected function prepareQuery(AdapterQuery $query)
    {
        foreach ($query->getState()->getDataTable()->getColumns() as $column) {
            if (null === $column->getField()) {
                $column->setOption('field', $column->getName());
            }
        }

        $query->setTotalRows($this->collection->count());
    }

    /**
     * {@inheritdoc}
     */
    protected function mapPropertyPath(AdapterQuery $query, AbstractColumn $column)
    {
        return '[' . implode('][', explode('.', $column->getField())) . ']';
    }

    /**
     * {@inheritdoc}
     */
    protected function getResults(AdapterQuery $query): \Traversable
    {
        $state = $query->getState();

        $filter = $this->buildFilter($state);
        $options = $this->buildOptions($state);

        $query->setFilteredRows($this->collection->count($filter));
        $cursor = $this->collection->find($filter, $options);
        $cursor->setTypeMap(['root' => 'array', 'document' => 'array']);

        /** @var BSONDocument $result */
        foreach ($cursor as $result) {
            array_walk_recursive($result, function (&$value) {
                if ($value instanceof UTCDateTime) {
                    $value = $value->toDateTime();
                }
            });

            yield $result;
        }
    }

    private function buildFilter(DataTableState $state): array
    {
        $filter = $this->filters;
        if (!empty($globalSearch = $state->getGlobalSearch())) {
            foreach ($state->getDataTable()->getColumns() as $column) {
                if ($column->isGlobalSearchable()) {
                    $filter[] = [$column->getField() => new \MongoDB\BSON\Regex($globalSearch, 'i')];
                }
            }
            $filter = ['$or' => $filter];
        }

        return $filter;
    }

    private function buildOptions(DataTableState $state): array
    {
        $options = [
            'limit' => $state->getLength(),
            'skip' => $state->getStart(),
            'sort' => [],
        ];

        foreach ($state->getOrderBy() as list($column, $direction)) {
            /** @var AbstractColumn $column */
            if ($column->isOrderable() && $orderField = $column->getOrderField()) {
                $options['sort'][$orderField] = self::SORT_MAP[$direction];
            }
        }

        return $options;
    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'filters' => [],
            ])
            ->setRequired(['collection'])
            ->setAllowedTypes('collection', \MongoDB\Collection::class)
            ->setAllowedTypes('filters', 'array')
        ;
    }
}
