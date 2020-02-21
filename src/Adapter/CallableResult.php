<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Adapter;

/**
 * CallableResult.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
final class CallableResult
{
    /** @var callable */
    private $callable;

    /** @var array */
    private $args;

    /**
     * CallableResult constructor.
     *
     * @param callable $callable
     * @param array $args
     */
    public function __construct(callable $callable, array $args = [])
    {
        $this->callable = $callable;
        $this->args = $args;
    }

    /**
     * @return callable
     */
    public function getCallable(): callable
    {
        return $this->callable;
    }

    /**
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }
}
