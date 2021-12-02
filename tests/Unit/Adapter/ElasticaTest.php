<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Tests\Unit\Adapter;

use Elastica\Response;
use Elastica\Transport\AbstractTransport;
use Omines\DataTablesBundle\Adapter\Elasticsearch\ElasticaAdapter;
use Omines\DataTablesBundle\Column\TextColumn;
use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableState;
use Omines\DataTablesBundle\Exporter\DataTableExporterManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;

/**
 * ElasticaTest.
 *
 * @author Niels Keurentjes <niels.keurentjes@omines.com>
 */
class ElasticaTest extends TestCase
{
    public function testElasticaAdapter()
    {
        // Set up expectations
        $transport = $this->getMockBuilder(AbstractTransport::class)
            ->setMethods(['exec'])
            ->getMock();
        $transport
            ->expects($this->exactly(2))
            ->method('exec')
            ->withConsecutive(
                [
                    $this->callback(function (\Elastica\Request $request) {
                        $this->assertSame('test-*/_search', $request->getPath());

                        $data = $request->getData();
                        $this->assertSame('foo', $data['query']['multi_match']['query']);
                        $this->assertSame(40, $data['size']);

                        return true;
                    }),
                ],
                [
                    $this->callback(function (\Elastica\Request $request) {
                        $this->assertSame('test-*/_search', $request->getPath());

                        $data = $request->getData();
                        $this->assertSame(20, $data['from']);
                        $this->assertArrayHasKey('bar', $data['sort'][0]);

                        return true;
                    }),
                ]
            )
            ->willReturn(new Response('{"took":10,"hits":{"total":2,"max_score":1.7144141,"hits":[{"foo":"baz","bar":"boz"},{"foo":"boz","bar":"baz"}]}}'))
        ;

        // Set up a dummy table
        $table = (new DataTable($this->createMock(EventDispatcher::class), $this->createMock(DataTableExporterManager::class)))
            ->setName('foo')
            ->setMethod(Request::METHOD_GET)
            ->add('foo', TextColumn::class, ['field' => 'foo', 'globalSearchable' => true])
            ->add('bar', TextColumn::class, ['field' => 'bar', 'globalSearchable' => false])
            ->createAdapter(ElasticaAdapter::class, [
                'index' => 'test-*',
                'client' => ['transport' => $transport],
            ])
        ;

        // Prepare dummy request
        $request = new Request([
            '_dt' => 'foo',
            'order' => [[
                'column' => 1,
                'dir' => 'desc',
            ]],
            'start' => 20,
            'length' => 40,
            'search' => [
                'value' => 'foo',
            ],
        ]);

        $this->assertTrue($table->handleRequest($request)->isCallback());
        $response = json_decode($table->getResponse()->getContent());
//        $this->assertEquals(2, $response->recordsTotal);
//        $this->assertEquals(2, $response->recordsFiltered);
//        $this->assertCount(2, $response->data);
    }

    /*
     * @expectedException \Omines\DataTablesBundle\Exception\MissingDependencyException
     * @expectedExceptionMessage Install ruflin/elastica to use the ElasticaAdapter
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
//    public function testMissingDependencyThrows()
//    {
//        foreach ($loaders = spl_autoload_functions() as $loader) {
//            spl_autoload_unregister($loader);
//        }
//        spl_autoload_register(function($class) use ($loaders) {
//            if ($class !== \Elastica\Client::class) {
//                foreach ($loaders as $loader) {
//                    call_user_func($loader, $class);
//                }
//            }
//        }, true, true);
//        (new ElasticaAdapter())->getData(new DataTableState(new DataTable()));
//    }
}
