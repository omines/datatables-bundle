<?php declare(strict_types=1);

namespace Omines\DataTablesBundle\Adapter\Doctrine;

use Doctrine\Common\Collections\Expr\Comparison;
use Doctrine\DBAL\Query\QueryBuilder;
use Doctrine\ORM\Query\Expr;
use Omines\DataTablesBundle\Adapter\AbstractAdapter;
use Omines\DataTablesBundle\Adapter\AdapterQuery;

use Omines\DataTablesBundle\Column\AbstractColumn;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DBALAdapter extends AbstractAdapter
{
    private QueryBuilder $queryBuilder;

    public function configure(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $this->queryBuilder = $options['queryBuilder'];
        $this->validateQueryBuilder();
    }

    private function validateQueryBuilder()
    {
        if ($this->queryBuilder->getMaxResults() !== null) {
            throw new \RuntimeException('QueryBuilder should not have a set maxResults. This is handled by the adapter.');
        }
        if ($this->queryBuilder->getFirstResult() !== 0) {
            throw new \RuntimeException('QueryBuilder should not have a set firstResult. This is handled by the adapter.');
        }
    }

    protected function prepareQuery(AdapterQuery $query)
    {
        foreach ($query->getState()->getDataTable()->getColumns() as $column) {
            if (null === $column->getField()) {
                $column->setOption('field', $column->getName());
            }
        }
    }

    protected function mapPropertyPath(AdapterQuery $query, AbstractColumn $column)
    {
        return "[{$column->getField()}]";
    }

    protected function getResults(AdapterQuery $query): \Traversable
    {
        $totalsQueryBuilder = (clone $this->queryBuilder)->select('COUNT(*)');
        $totalRows = (int)$totalsQueryBuilder->executeQuery()->fetchOne();
        $query->setTotalRows($totalRows);
        unset($totalsQueryBuilder);

        // Set order
        foreach ($query->getState()->getOrderBy() as [$column, $direction]) {
            /** @var AbstractColumn $column */
            if ($column->isOrderable()) {
                $this->queryBuilder->addOrderBy("`{$column->getOrderField()}`", $direction);
            }
        }

        // Deal with pagination
        if ($query->getState()->getLength() > 0) {
            $this->queryBuilder
                ->setFirstResult($query->getState()->getStart())
                ->setMaxResults($query->getState()->getLength())
            ;
        }

        // Handle global search
        if (!empty($globalSearch = $query->getState()->getGlobalSearch())) {
            $globalSearchCriteria = [];
            foreach ($query->getState()->getDataTable()->getColumns() as $column) {
                if ($column->isGlobalSearchable()) {
                    $globalSearchCriteria[] = $this->queryBuilder->expr()->like("`{$column->getField()}`", "'%{$globalSearch}%'");
                }
            }
            $this->queryBuilder->andWhere($this->queryBuilder->expr()->orX(...$globalSearchCriteria));
        }

        // @todo Implement column filtering

        $result = $this->queryBuilder->executeQuery();

        $query->setFilteredRows($totalRows-$result->rowCount());

        while ($row = $result->fetchAssociative()) {
            yield $row;
        }

    }

    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([])
            ->setRequired(['queryBuilder'])
            ->setAllowedTypes('queryBuilder', QueryBuilder::class)
        ;
    }

}
