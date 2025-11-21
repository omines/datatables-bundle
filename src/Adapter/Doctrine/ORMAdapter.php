<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Adapter\Doctrine;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
use Omines\DataTablesBundle\Adapter\AbstractAdapter;
use Omines\DataTablesBundle\Adapter\AdapterQuery;
use Omines\DataTablesBundle\Adapter\Doctrine\Event\ORMAdapterQueryEvent;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\AutomaticQueryBuilder;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\QueryBuilderProcessorInterface;
use Omines\DataTablesBundle\Adapter\Doctrine\ORM\SearchCriteriaProvider;
use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\DataTableState;
use Omines\DataTablesBundle\Exception\InvalidConfigurationException;
use Omines\DataTablesBundle\Exception\MissingDependencyException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/*
 * Help opcache.preload discover always-needed symbols
 * @link https://github.com/omines/datatables-bundle/issues/288
 * @link https://github.com/php/php-src/issues/10131
 */
interface_exists(QueryBuilderProcessorInterface::class);

/**
 * ORMAdapter.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 * @author Robbert Beesems <robbert.beesems@omines.com>
 *
 * @phpstan-type HydrationMode AbstractQuery::HYDRATE_*
 * @phpstan-type ORMOptions array{entity: class-string, hydrate: HydrationMode, query: QueryBuilderProcessorInterface[], criteria: QueryBuilderProcessorInterface[]}
 */
class ORMAdapter extends AbstractAdapter
{
    private ManagerRegistry $registry;
    protected EntityManager $manager;
    protected ClassMetadata $metadata;

    /** @var ?HydrationMode */
    private ?int $hydrationMode = null;

    /** @var QueryBuilderProcessorInterface[] */
    private array $queryBuilderProcessors = [];

    /** @var QueryBuilderProcessorInterface[] */
    protected array $criteriaProcessors = [];

    public function __construct(?ManagerRegistry $registry = null)
    {
        if (null === $registry) {
            throw new MissingDependencyException('Install doctrine/doctrine-bundle to use the ORMAdapter');
        }

        parent::__construct();
        $this->registry = $registry;
    }

    /**
     * @param array<string, mixed> $options
     */
    public function configure(array $options): void
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        /** @var ORMOptions $options */
        $options = $resolver->resolve($options);

        $this->afterConfiguration($options);
    }

    public function addCriteriaProcessor(callable|QueryBuilderProcessorInterface $processor): void
    {
        $this->criteriaProcessors[] = $this->normalizeProcessor($processor);
    }

    protected function prepareQuery(AdapterQuery $query): void
    {
        $state = $query->getState();
        $query->set('qb', $builder = $this->createQueryBuilder($state));
        $query->set('rootAlias', $rootAlias = $builder->getDQLPart('from')[0]->getAlias());

        // Provide default field mappings if needed
        foreach ($state->getDataTable()->getColumns() as $column) {
            if (null === $column->getField() && isset($this->metadata->fieldMappings[$name = $column->getName()])) {
                $column->setOption('field', "{$rootAlias}.{$name}");
            }
        }

        /** @var Query\Expr\From $fromClause */
        $fromClause = $builder->getDQLPart('from')[0];
        $identifier = "{$fromClause->getAlias()}.{$this->metadata->getSingleIdentifierFieldName()}";
        $query->setTotalRows($this->getCount($builder, $identifier));

        // Get record count after filtering
        $this->buildCriteria($builder, $state);
        $query->setFilteredRows($this->getCount($builder, $identifier));

        // Perform mapping of all referred fields and implied fields
        $aliases = $this->getAliases($query);
        $query->set('aliases', $aliases);
        $query->setIdentifierPropertyPath($this->mapFieldToPropertyPath($identifier, $aliases));
    }

    /**
     * @return array<string, array<string|int|null, ClassMetadata|string|null>>
     */
    protected function getAliases(AdapterQuery $query): array
    {
        /** @var QueryBuilder $builder */
        $builder = $query->get('qb');
        $aliases = [];

        /** @var Query\Expr\From $from */
        foreach ($builder->getDQLPart('from') as $from) {
            $aliases[$from->getAlias()] = [null, $this->manager->getMetadataFactory()->getMetadataFor($from->getFrom())];
        }

        // Alias all joins
        foreach ($builder->getDQLPart('join') as $joins) {
            /** @var Query\Expr\Join $join */
            foreach ($joins as $join) {
                if (false === mb_strstr($join->getJoin(), '.')) {
                    continue;
                }

                list($origin, $target) = explode('.', $join->getJoin());

                assert(is_string($alias = $join->getAlias()));
                $mapping = $aliases[$origin][1]->getAssociationMapping($target);
                $aliases[$alias] = [$join->getJoin(), $this->manager->getMetadataFactory()->getMetadataFor($mapping['targetEntity'])];
            }
        }

        return $aliases;
    }

    protected function mapPropertyPath(AdapterQuery $query, AbstractColumn $column): ?string
    {
        if (null === ($field = $column->getField())) {
            throw new InvalidConfigurationException(sprintf('Could not automatically map a field for column "%s"', $column->getName()));
        }

        return $this->mapFieldToPropertyPath($field, $query->get('aliases'));
    }

    /**
     * @return \Traversable<mixed[]>
     */
    protected function getResults(AdapterQuery $query): \Traversable
    {
        /** @var QueryBuilder $builder */
        $builder = $query->get('qb');
        $state = $query->getState();

        // Apply definitive view state for current 'page' of the table
        foreach ($state->getOrderBy() as list($column, $direction)) {
            /** @var AbstractColumn $column */
            if ($column->isOrderable() && null !== ($order = $column->getOrderField())) {
                $builder->addOrderBy($order, $direction);
            }
        }
        if (null !== $state->getLength()) {
            $builder
                ->setFirstResult($state->getStart())
                ->setMaxResults($state->getLength())
            ;
        }

        $query = $builder->getQuery();
        $event = new ORMAdapterQueryEvent($query);
        $state->getDataTable()->getEventDispatcher()->dispatch($event, ORMAdapterEvents::PRE_QUERY);

        foreach ($query->toIterable([], $this->hydrationMode) as $entity) {
            yield $entity;
            if (AbstractQuery::HYDRATE_OBJECT === $this->hydrationMode) {
                $this->manager->detach($entity);
            }
        }
    }

    protected function buildCriteria(QueryBuilder $queryBuilder, DataTableState $state): void
    {
        foreach ($this->criteriaProcessors as $provider) {
            $provider->process($queryBuilder, $state);
        }
    }

    protected function createQueryBuilder(DataTableState $state): QueryBuilder
    {
        $queryBuilder = $this->manager->createQueryBuilder();

        // Run all query builder processors in order
        foreach ($this->queryBuilderProcessors as $processor) {
            $processor->process($queryBuilder, $state);
        }

        return $queryBuilder;
    }

    protected function getCount(QueryBuilder $queryBuilder, mixed $identifier): int
    {
        $qb = clone $queryBuilder;

        $qb->resetDQLPart('orderBy');
        $gb = $qb->getDQLPart('groupBy');
        if (empty($gb) || !$this->hasGroupByPart($identifier, $gb)) {
            $qb->select($qb->expr()->count($identifier));

            return (int) $qb->getQuery()->getSingleScalarResult();
        } else {
            $qb->resetDQLPart('groupBy');
            $qb->select($qb->expr()->countDistinct($identifier)); /* @phpstan-ignore-line */

            return (int) $qb->getQuery()->getSingleScalarResult();
        }
    }

    /**
     * @param Query\Expr\GroupBy[] $gbList
     */
    protected function hasGroupByPart(string $identifier, array $gbList): bool
    {
        foreach ($gbList as $gb) {
            if (in_array($identifier, $gb->getParts(), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param array<string, mixed[]> $aliases
     */
    protected function mapFieldToPropertyPath(string $field, array $aliases = []): string
    {
        $parts = explode('.', $field);
        if (count($parts) < 2) {
            throw new InvalidConfigurationException(sprintf("Field name '%s' must consist at least of an alias and a field separated with a period", $field));
        }

        $origin = $parts[0];
        array_shift($parts);
        $target = array_reverse($parts);
        $path = $target;

        $current = isset($aliases[$origin]) ? $aliases[$origin][0] : null;

        while (null !== $current) {
            list($origin, $target) = explode('.', $current);
            $path[] = $target;
            $current = $aliases[$origin][0];
        }

        if (AbstractQuery::HYDRATE_ARRAY === $this->hydrationMode) {
            return '[' . implode('][', array_reverse($path)) . ']';
        } else {
            return implode('.', array_reverse($path));
        }
    }

    protected function configureOptions(OptionsResolver $resolver): void
    {
        $providerNormalizer = function (Options $options, $value) {
            return array_map([$this, 'normalizeProcessor'], (array) $value);
        };

        $resolver
            ->setDefaults([
                'hydrate' => Query::HYDRATE_OBJECT,
                'query' => [],
                'criteria' => function (Options $options) {
                    return [new SearchCriteriaProvider()];
                },
            ])
            ->setRequired('entity')
            ->setAllowedTypes('entity', ['string'])
            ->setAllowedTypes('hydrate', 'int')
            ->setAllowedTypes('query', [QueryBuilderProcessorInterface::class, 'array', 'callable'])
            ->setAllowedTypes('criteria', [QueryBuilderProcessorInterface::class, 'array', 'callable', 'null'])
            ->setNormalizer('query', $providerNormalizer)
            ->setNormalizer('criteria', $providerNormalizer)
        ;
    }

    /**
     * @param ORMOptions $options
     */
    protected function afterConfiguration(array $options): void
    {
        // Enable automated mode or just get the general default entity manager
        $manager = $this->registry->getManagerForClass($options['entity']);
        if (!$manager instanceof EntityManager) {
            throw new InvalidConfigurationException(sprintf('Doctrine has no valid entity manager for entity "%s", is it correctly imported and referenced?', $options['entity']));
        }
        $this->manager = $manager;
        $this->metadata = $this->manager->getClassMetadata($options['entity']);
        if (empty($options['query'])) {
            $options['query'] = [new AutomaticQueryBuilder($this->manager, $this->metadata)];
        }

        // Set options
        $this->hydrationMode = $options['hydrate'];
        $this->queryBuilderProcessors = $options['query'];
        $this->criteriaProcessors = $options['criteria'];
    }

    private function normalizeProcessor(callable|QueryBuilderProcessorInterface $provider): QueryBuilderProcessorInterface
    {
        if ($provider instanceof QueryBuilderProcessorInterface) {
            return $provider;
        } elseif (is_callable($provider)) {
            return new class($provider) implements QueryBuilderProcessorInterface {
                /** @var callable */
                private mixed $callable;

                public function __construct(callable $value)
                {
                    $this->callable = $value;
                }

                public function process(QueryBuilder $queryBuilder, DataTableState $state): void
                {
                    call_user_func($this->callable, $queryBuilder, $state);
                }
            };
        }
    }
}
