<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Unit;

use Omines\DataTablesBundle\Exception\MissingDependencyException;
use Omines\DataTablesBundle\Twig\DataTablesExtension;
use Omines\DataTablesBundle\Twig\TwigRenderer;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Translation\Translator;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * TwigTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class TwigTest extends TestCase
{
    public function testExtensionName()
    {
        /** @var TranslatorInterface $translator */
        $translator = $this->createMock(Translator::class);

        $twig = new DataTablesExtension($translator);
        $this->assertSame('DataTablesBundle', $twig->getName());
    }

    public function testMissingTwigBundleThrows()
    {
        $this->expectException(MissingDependencyException::class);
        $this->expectExceptionMessage('You must have symfony/twig-bundle installed');

        new TwigRenderer();
    }
}
