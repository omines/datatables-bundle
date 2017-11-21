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

use Doctrine\Common\Collections\Criteria;
use Omines\DataTablesBundle\Adapter\AbstractAdapter;
use Omines\DataTablesBundle\DataTableState;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * DoctrineAdapter.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
abstract class DoctrineAdapter extends AbstractAdapter
{
    /** @var RegistryInterface */
    protected $registry;

    /** @var CriteriaProviderInterface[] */
    protected $criteriaProviders;

    /**
     * DoctrineAdapter constructor.
     *
     * @param RegistryInterface $registry
     */
    public function __construct(RegistryInterface $registry)
    {
        parent::__construct();
        $this->registry = $registry;
    }

    /**
     * {@inheritdoc}
     */
    final public function configure(array $options)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);
        $options = $resolver->resolve($options);

        $this->handleOptions($options);
    }

    /**
     * @param mixed $provider
     */
    public function addCriteriaProvider($provider)
    {
        $this->criteriaProviders[] = $this->normalizeProvider($provider);
    }

    /**
     * @param array $options
     */
    protected function handleOptions(array $options)
    {
        $this->criteriaProviders = $options['criteria'];
    }

    /**
     * @param OptionsResolver $resolver
     */
    protected function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefaults([
                'criteria' => null,
            ])
            ->setAllowedTypes('criteria', [CriteriaProviderInterface::class, 'array', 'callable', 'null'])
            ->setNormalizer('criteria', function (Options $options, $value) {
                if (null === $value) {
                    return [new SearchCriteriaProvider()];
                }

                return array_map([$this, 'normalizeProvider'], (array) $value);
            });
    }

    /**
     * @param callable|CriteriaProviderInterface $provider
     * @return CriteriaProviderInterface
     */
    private function normalizeProvider($provider)
    {
        if (is_callable($provider)) {
            return new class($provider) implements CriteriaProviderInterface {
                private $callable;

                public function __construct(callable $value)
                {
                    $this->callable = $value;
                }

                public function process(DataTableState $state)
                {
                    return call_user_func($this->callable, $state);
                }
            };
        } elseif ($provider instanceof CriteriaProviderInterface) {
            return $provider;
        }

        throw new \LogicException('CriteriaProvider must be a callable or implement CriteriaProviderInterface');
    }
}
