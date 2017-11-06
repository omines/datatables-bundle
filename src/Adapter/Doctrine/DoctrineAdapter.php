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

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Criteria;
use Omines\DataTablesBundle\Adapter\AdapterInterface;
use Omines\DataTablesBundle\DataTableState;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * DoctrineAdapter.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
abstract class DoctrineAdapter implements AdapterInterface
{
    /** @var Registry */
    protected $registry;

    /** @var CriteriaProviderInterface[] */
    protected $criteriaProcessors;

    /**
     * DoctrineAdapter constructor.
     *
     * @param Registry $registry
     */
    public function __construct(Registry $registry)
    {
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

    protected function handleOptions(array $options)
    {
        $this->criteriaProcessors = $options['criteria'];
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
                } elseif (is_object($value)) {
                    return [$value];
                } elseif (!is_callable($value)) {
                    return $value;
                }

                return new class($value) implements CriteriaProviderInterface {
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
            });
    }
}
