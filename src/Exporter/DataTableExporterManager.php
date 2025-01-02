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
use Omines\DataTablesBundle\Exception\UnknownDataTableExporterException;
use Omines\DataTablesBundle\Exporter\Event\DataTableExporterResponseEvent;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\OptionsResolver\OptionsResolver;
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

    /** @var TranslatorInterface */
    private $translator;

    public function __construct(DataTableExporterCollection $exporterCollection, TranslatorInterface $translator)
    {
        $this->exporterCollection = $exporterCollection;
        $this->translator = $translator;
    }

    public function setExporterName(string $exporterName): static
    {
        $this->exporterName = $exporterName;

        return $this;
    }

    public function setDataTable(DataTable $dataTable): static
    {
        $this->dataTable = $dataTable;

        return $this;
    }

    /**
     * @throws UnknownDataTableExporterException when the exporter cannot be found
     */
    public function getResponse(): Response
    {
        $file = $this->getExport();

        $response = new BinaryFileResponse($file);
        $response->deleteFileAfterSend(true);
        $response->headers->set('Content-Type', $this->getExporter()->getMimeType());
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_ATTACHMENT, $response->getFile()->getFilename());

        $this->dataTable->getEventDispatcher()->dispatch(new DataTableExporterResponseEvent($response), DataTableExporterEvents::PRE_RESPONSE);

        return $response;
    }

    /**
     * @throws UnknownDataTableExporterException when the exporter cannot be found
     */
    public function getExporter(): DataTableExporterInterface
    {
        return $this->exporterCollection->getByName($this->exporterName);
    }

    /**
     * @throws UnknownDataTableExporterException when the exporter cannot be found
     */
    public function getExport(): \SplFileInfo
    {
        return $this->getExporter()->export($this->getColumnNames(), $this->getAllData(), $this->getColumnOptions());
    }

    /**
     * @return list<array<string, mixed>>
     * @throws UnknownDataTableExporterException when the exporter cannot be found
     */
    private function getColumnOptions(): array
    {
        $resolver = new OptionsResolver();
        $this->getExporter()->configureColumnOptions($resolver);

        $options = [];
        foreach ($this->dataTable->getColumns() as $column) {
            // For each column, resolve the exporter options set on that column
            $options[] = $resolver->resolve($column->getExporterOptions($this->exporterName));
        }

        return $options;
    }

    /**
     * The translated column names.
     *
     * @return list<string>
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
