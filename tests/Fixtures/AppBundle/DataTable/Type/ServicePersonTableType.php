<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Fixtures\AppBundle\DataTable\Type;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableTypeInterface;

/**
 * ServicePersonTableType.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class ServicePersonTableType implements DataTableTypeInterface
{
    /** @var Registry */
    private $registry;

    /**
     * ServicePersonTableType constructor.
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
    public function configure(DataTable $dataTable)
    {
        // TODO: Implement configure() method.
    }
}
