<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Column;

use Omines\DataTablesBundle\Exception\MissingDependencyException;
use Twig\Environment;
use Twig\Extension\StringLoaderExtension;

/**
 * TwigStringColumn.
 *
 * @author Marek Víger <marek.viger@gmail.com>
 */
class TwigStringColumn extends TwigColumn
{
    public function __construct(Environment $twig = null)
    {
        parent::__construct($twig);

        if (!$this->twig->hasExtension(StringLoaderExtension::class)) {
            throw new MissingDependencyException('You must have StringLoaderExtension enabled to use ' . self::class);
        }
    }

    protected function render($value, $context): mixed
    {
        return $this->twig->render('@DataTables/Column/twig_string.html.twig', [
            'column' => $this,
            'column_template' => $this->getTemplate(),
            'row' => $context,
            'value' => $value,
        ]);
    }
}
