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
use Omines\DataTablesBundle\Exporter\Event\DataTableExporterResponseEvent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * DataTableExporterManager.
 *
 * @author Maxime Pinot <contact@maximepinot.com>
 */
class DataTableExporterManager
{
    private DataTable $dataTable;

    private string $exporterName;

    /**
     * DataTableExporterManager constructor.
     */
    public function __construct(private DataTableExporterCollection $exporterCollection, private TranslatorInterface $translator)
    {}

    public function setExporterName(string $exporterName): self
    {
        $this->exporterName = $exporterName;

        return $this;
    }

    public function setDataTable(DataTable $dataTable): self
    {
        $this->dataTable = $dataTable;

        return $this;
    }

    /**
     * @throws \Omines\DataTablesBundle\Exception\UnknownDataTableExporterException
     */
    public function getResponse(): Response
    {
        $exporter = $this->exporterCollection->getByName($this->exporterName);
        $file = $exporter->export($this->getColumnNames(), $this->getAllData());

        $response = new BinaryFileResponse($file);
        $response->deleteFileAfterSend(true);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $response->getFile()->getFilename());

        $this->dataTable->getEventDispatcher()->dispatch(new DataTableExporterResponseEvent($response), DataTableExporterEvents::PRE_RESPONSE);

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
     * Browse the entire DataTable (all pages).
     *
     * A Generator is created in order to remove the 'DT_RowId' key
     * which is created by some adapters (e.g. ORMAdapter).
     */
    private function getAllData(): \Iterator
    {
        $data = $this->dataTable
            ->getAdapter()
            ->getData($this->dataTable->getState()->setStart(0)->setLength(null))
            ->getData();

        foreach ($data as $row) {
            if (array_key_exists('DT_RowId', $row)) {
                unset($row['DT_RowId']);
            }

            yield $row;
        }
    }
}
