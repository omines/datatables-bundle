<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Twig;

use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableRendererInterface;
use Symfony\Bundle\TwigBundle\TwigEngine;

/**
 * TwigRenderer.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class TwigRenderer implements DataTableRendererInterface
{
    /** @var TwigEngine */
    private $twig;

    /**
     * DataTableRenderer constructor.
     *
     * @param TwigEngine @twig
     */
    public function __construct(TwigEngine $twig = null)
    {
        if (null === ($this->twig = $twig)) {
            throw new \LogicException('You must have TwigBundle installed to use the default Twig based DataTables rendering');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function renderDataTable(DataTable $dataTable, string $template): string
    {
        return $this->twig->render($template, [
            'datatable' => $dataTable,
        ]);
    }
}
