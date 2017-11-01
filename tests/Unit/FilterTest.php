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

use Omines\DataTablesBundle\Filter\ChoiceFilter;
use Omines\DataTablesBundle\Filter\TextFilter;
use PHPUnit\Framework\TestCase;

/**
 * FilterTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class FilterTest extends TestCase
{
    public function testChoiceFilter()
    {
        $filter = new ChoiceFilter();

        // Test defaults
        $this->assertEmpty($filter->getChoices());
        $this->assertNull($filter->getPlaceholder());

        $filter->setChoices(['foo' => 'bar', 'bar' => 'baz']);
        $this->assertTrue($filter->isValidValue('foo'));
        $this->assertFalse($filter->isValidValue('baz'));

        $filter->set([
            'template_html' => 'foobar.html',
            'operator' => 'bar',
        ]);
        $this->assertSame('foobar.html', $filter->getTemplateHtml());
        $this->assertSame('@DataTables/Filter/select.js.twig', $filter->getTemplateJs());
        $this->assertSame('bar', $filter->getOperator());
    }

    public function testTextFilter()
    {
        $filter = new TextFilter();

        // Test defaults
        $this->assertNull($filter->getPlaceholder());
        $this->assertTrue($filter->isValidValue('foo'));

        $filter->set([
            'template_js' => 'foobar.js',
            'operator' => 'foo',
        ]);
        $this->assertSame('@DataTables/Filter/text.html.twig', $filter->getTemplateHtml());
        $this->assertSame('foobar.js', $filter->getTemplateJs());
        $this->assertSame('foo', $filter->getOperator());
    }
}
