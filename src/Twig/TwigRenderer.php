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
use Omines\DataTablesBundle\Exception\MissingDependencyException;
use Twig\Environment;

/**
 * TwigRenderer.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class TwigRenderer implements DataTableRendererInterface
{
    /** @var Twig_Environment */
    private $twig;

    /**
     * DataTableRenderer constructor.
     *
     * @param Environment $twig
     */
    public function __construct(Environment $twig = null)
    {
        if (null === ($this->twig = $twig)) {
            throw new MissingDependencyException('You must have symfony/twig-bundle installed to use the default Twig based DataTables rendering');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function renderDataTable(DataTable $dataTable, string $template, array $parameters): string
    {
        $parameters['datatable'] = $dataTable;

        return $this->twig->render($template, $parameters);
    }

    /**
     * {@inheritdoc}
     */
    public function getColumnRenderer($template): callable
    {
        $renderer = $this->twig->load($template);

        return function ($column, $value, $context) use ($renderer) {
            $params = [
                '_column' => $column,
                'value' => $value,
                'context' => $context,
            ];
            $blockName = $column->getName() . '_column';
            if ($renderer->hasBlock($blockName)) {
                return $renderer->renderBlock($blockName, $params);
            } else {
                return $value;
            }
        };
    }
}
