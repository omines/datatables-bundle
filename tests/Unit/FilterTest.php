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
    }
}
