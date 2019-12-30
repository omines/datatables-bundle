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
use Omines\DataTablesBundle\Exception\InvalidArgumentException;
use Omines\DataTablesBundle\Exporter\Event\DataTableExporterResponseEvent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Translation\TranslatorInterface as LegacyTranslatorInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

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

    /** @var TranslatorInterface|LegacyTranslatorInterface */
    private $translator;

    /**
     * DataTableExporterManager constructor.
     *
     * @param DataTableExporterCollection $exporterCollection
     * @param TranslatorInterface|LegacyTranslatorInterface $translator
     */
    public function __construct(DataTableExporterCollection $exporterCollection, $translator)
    {
        if (!$translator instanceof TranslatorInterface && !$translator instanceof LegacyTranslatorInterface) {
            throw new InvalidArgumentException(sprintf('Expected an instance of "Symfony\Contracts\Translation\TranslatorInterface" or "Symfony\Component\Translation\TranslatorInterface". Got "%s" instead.', is_object($translator) ? get_class($translator) : gettype($translator)));
        }

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
     *
     * @return \Iterator
     */
    private function getAllData(): \Iterator
    {
        $data = $this->dataTable->getAdapter()->getData(new DataTableState($this->dataTable))->getData();

        foreach ($data as $row) {
            if (isset($row['DT_RowId'])) {
                $row = array_splice($row, 1);
            }

            yield $row;
        }
    }
}
