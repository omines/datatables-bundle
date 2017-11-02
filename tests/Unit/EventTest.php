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

use Omines\DataTablesBundle\Event\Callback;
use Omines\DataTablesBundle\Event\Event;
use PHPUnit\Framework\TestCase;

/**
 * EventTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class EventTest extends TestCase
{
    public function testEvent()
    {
        $event = new Event();
        $event->set([
            'template' => 'foo',
            'type' => Event::COLUMN_SIZING,
            'vars' => [1, 2, 3],
        ]);

        $this->assertSame('foo', $event->getTemplate());
        $this->assertSame(Event::COLUMN_SIZING, $event->getType());
        $this->assertSame([1, 2, 3], $event->getVars());
    }

    public function testCallback()
    {
        $callback = new Callback();
        $callback->set([
            'template' => 'bar',
            'type' => Callback::HEADER_CALLBACK,
        ]);

        $this->assertSame('bar', $callback->getTemplate());
        $this->assertSame(Callback::HEADER_CALLBACK, $callback->getType());
        $this->assertEmpty($callback->getVars());
    }
}
