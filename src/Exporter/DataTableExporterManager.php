<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Exporter;

use Omines\DataTablesBundle\DataTable;
use Omines\DataTablesBundle\DataTableState;
use Omines\DataTablesBundle\Exporter\Event\DataTableExporterResponseEvent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\DataCollectorTranslator;

/**
 * DataTableExporterManager.
 *
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class DataTableExporterManager
{
    /** @var DataTable */
    private $dataTable;

    /** @var DataTableExporterCollection */
    private $exporterCollection;

    /** @var string */
    private $exporterName;

    /** @var DataCollectorTranslator */
    private $translator;

    /**
     * DataTableExporterManager constructor.
     *
     * @param DataTableExporterCollection $exporterCollection
     * @param DataCollectorTranslator $translator
     */
    public function __construct(DataTableExporterCollection $exporterCollection, DataCollectorTranslator $translator)
    {
        $this->exporterCollection = $exporterCollection;
        $this->translator = $translator;
    }

    /**
     * @param string $exporterName
     *
     * @return DataTableExporterManager
     */
    public function setExporterName(string $exporterName): self
    {
        $this->exporterName = $exporterName;

        return $this;
    }

    /**
     * @param DataTable $dataTable
     *
     * @return DataTableExporterManager
     */
    public function setDataTable(DataTable $dataTable): self
    {
        $this->dataTable = $dataTable;

        return $this;
    }

    /**
     * @return Response
     *
     * @throws \Omines\DataTablesBundle\Exception\UnknownDataTableExporterException
     */
    public function getResponse(): Response
    {
        $exporter = $this->exporterCollection->getByName($this->exporterName);
        $file = $exporter->export($this->getColumnNames(), $this->getAllData());

        $response = new BinaryFileResponse($file);
        $response->deleteFileAfterSend();
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $response->getFile()->getFilename());

        $responseEvent = new DataTableExporterResponseEvent($response);
        $this->dataTable->getEventDispatcher()->dispatch(DataTableExporterEvents::PRE_RESPONSE, $responseEvent);

        return $response;
    }

    /**
     * The translated column names.
     *
     * @return string[]
     */
    private function getColumnNames(): array
    {
        $columns = [];

        foreach ($this->dataTable->getColumns() as $column) {
            $columns[] = $this->translator->trans($column->getLabel(), [], $this->dataTable->getTranslationDomain());
        }

        return $columns;
    }

    /**
     * Creates an Iterator to browse the entire DataTable (all pages).
     *
     * A custom iterator is created in order to remove the 'DT_RowId' key
     * which is created by some adapters (e.g. ORMAdapter).
     *
     * @return \Iterator
     */
    private function getAllData(): \Iterator
    {
        $data = $this->dataTable->getAdapter()->getData(new DataTableState($this->dataTable))->getData();

        return new class($data) implements \Iterator {
            private $data;
            private $currentPosition;

            public function __construct(\Traversable $data)
            {
                $this->data = $data;
                $this->currentPosition = 0;
            }

            public function current()
            {
                $d = $this->data[$this->currentPosition];

                if (isset($d['DT_RowId'])) {
                    $d = array_splice($d, 1);
                }

                return $d;
            }

            public function next()
            {
                ++$this->currentPosition;
            }

            public function key()
            {
                return $this->currentPosition;
            }

            public function valid()
            {
                return isset($this->data[$this->currentPosition]);
            }

            public function rewind()
            {
                $this->currentPosition = 0;
            }
        };
    }
}
