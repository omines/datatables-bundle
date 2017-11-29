<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Omines\DataTablesBundle\Twig;

use Omines\DataTablesBundle\Column\AbstractColumn;
use Omines\DataTablesBundle\DataTable;
use Symfony\Component\Translation\TranslatorInterface;

class DataTablesExtension extends \Twig_Extension
{
    /** @var TranslatorInterface */
    protected $translator;

    /**
     * DataTablesExtension constructor.
     *
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('datatable', [$this, 'datatable'],
                ['is_safe' => ['html'], 'needs_environment' => true]),
            new \Twig_SimpleFunction('datatable_html', [$this, 'datatableHtml'],
                ['is_safe' => ['html'], 'needs_environment' => true]),
            new \Twig_SimpleFunction('datatable_js', [$this, 'datatableJs'],
                ['is_safe' => ['html'], 'needs_environment' => true]),
            new \Twig_SimpleFunction('datatable_settings', function (DataTable $dataTable) {
                return json_encode([
                    'name' => $dataTable->getName(),
                    'method' => $dataTable->getMethod(),
                    'options' => [
                        'language' => $this->getLanguageSettings($dataTable),
                    ],
                ]);
            }, ['is_safe' => ['html']]),
        ];
    }

    public function datatable(\Twig_Environment $twig, DataTable $datatable, $options = [], $parameters = [])
    {
        return $this->render($twig, '@DataTables/datatable.html.twig', $datatable, $options, $parameters);
    }

    public function datatableHtml(\Twig_Environment $twig, DataTable $datatable, $options = [], $parameters = [])
    {
        return $this->render($twig, '@DataTables/datatable_html.html.twig', $datatable, $options, $parameters);
    }

    public function datatableJs(\Twig_Environment $twig, DataTable $datatable, $options = [], $parameters = [])
    {
        return $this->render($twig, '@DataTables/datatable_js.html.twig', $datatable, $options, $parameters);
    }

    private function render(\Twig_Environment $twig, string $template, DataTable $datatable, $options = [], $parameters = [])
    {
        return $twig->render($template, array_merge([
            'datatable' => $datatable,
            'options' => $this->getOptions($datatable, $options),
        ], $parameters));
    }

    private function getOptions(DataTable $datatable, $options)
    {
        $result = array_merge($datatable->getOptions(), $options);

        $result['columns'] = array_map(
            function (AbstractColumn $column) {
                return [
                    'data' => $column->getName(),
                    'orderable' => $column->isOrderable(),
                    'searchable' => $column->isSearchable(),
                    'visible' => $column->isVisible(),
                    'className' => $column->getClassName(),
                ];
            }, $datatable->getColumns());

        $result['language'] = $this->getLanguageSettings($datatable);

        return $result;
    }

    /**
     * @param DataTable $dataTable
     * @return array
     */
    private function getLanguageSettings(DataTable $dataTable)
    {
        $locale = $this->translator->getLocale();
        if ($dataTable->getSetting('language_from_cdn') && array_key_exists($locale, $this->languageCDNFile)) {
            return ['url' => "//cdn.datatables.net/plug-ins/1.10.15/i18n/{$this->languageCDNFile[$locale]}"];
        } else {
            return [
                'processing' => $this->translator->trans('datatable.datatable.processing'),
                'search' => $this->translator->trans('datatable.datatable.search'),
                'lengthMenu' => $this->translator->trans('datatable.datatable.lengthMenu'),
                'info' => $this->translator->trans('datatable.datatable.info'),
                'infoEmpty' => $this->translator->trans('datatable.datatable.infoEmpty'),
                'infoFiltered' => $this->translator->trans('datatable.datatable.infoFiltered'),
                'infoPostFix' => $this->translator->trans('datatable.datatable.infoPostFix'),
                'loadingRecords' => $this->translator->trans('datatable.datatable.loadingRecords'),
                'zeroRecords' => $this->translator->trans('datatable.datatable.zeroRecords'),
                'emptyTable' => $this->translator->trans('datatable.datatable.emptyTable'),
                'searchPlaceholder' => $this->translator->trans('datatable.datatable.searchPlaceholder'),
                'paginate' => [
                    'first' => $this->translator->trans('datatable.datatable.paginate.first'),
                    'previous' => $this->translator->trans('datatable.datatable.paginate.previous'),
                    'next' => $this->translator->trans('datatable.datatable.paginate.next'),
                    'last' => $this->translator->trans('datatable.datatable.paginate.last'),
                ],
                'aria' => [
                    'sortAscending' => $this->translator->trans('datatable.datatable.aria.sortAscending'),
                    'sortDescending' => $this->translator->trans('datatable.datatable.aria.sortDescending'),
                ],
            ];
        }
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'DataTablesBundle';
    }

    protected $callbackMethodName = [
        'createdRow',
        'drawCallback',
        'footerCallback',
        'formatNumber',
        'headerCallback',
        'infoCallback',
        'initComplete',
        'preDrawCallback',
        'rowCallback',
        'stateLoadCallback',
        'stateLoaded',
        'stateLoadParams',
        'stateSaveCallback',
        'stateSaveParams',
    ];

    protected $languageCDNFile = [
        'af' => 'Afrikaans.json',
        'ar' => 'Arabic.json',
        'az' => 'Azerbaijan.json',
        'be' => 'Belarusian.json',
        'bg' => 'Bulgarian.json',
        'bn' => 'Bangla.json',
        'ca' => 'Catalan.json',
        'cs' => 'Czech.json',
        'cy' => 'Welsh.json',
        'da' => 'Danish.json',
        'de' => 'German.json',
        'el' => 'Greek.json',
        'en' => 'English.json',
        'es' => 'Spanish.json',
        'et' => 'Estonian.json',
        'eu' => 'Basque.json',
        'fa' => 'Persian.json',
        'fi' => 'Finnish.json',
        'fr' => 'French.json',
        'ga' => 'Irish.json',
        'gl' => 'Galician.json',
        'gu' => 'Gujarati.json',
        'he' => 'Hebrew.json',
        'hi' => 'Hindi.json',
        'hr' => 'Croatian.json',
        'hu' => 'Hungarian.json',
        'hy' => 'Armenian.json',
        'id' => 'Indonesian.json',
        'is' => 'Icelandic.json',
        'it' => 'Italian.json',
        'ja' => 'Japanese.json',
        'ka' => 'Georgian.json',
        'ko' => 'Korean.json',
        'lt' => 'Lithuanian.json',
        'lv' => 'Latvian.json',
        'mk' => 'Macedonian.json',
        'mn' => 'Mongolian.json',
        'ms' => 'Malay.json',
        'nb' => 'Norwegian.json',
        'ne' => 'Nepali.json',
        'nl' => 'Dutch.json',
        'nn' => 'Norwegian.json',
        'pl' => 'Polish.json',
        'ps' => 'Pashto.json',
        'pt' => 'Portuguese.json',
        'ro' => 'Romanian.json',
        'ru' => 'Russian.json',
        'si' => 'Sinhala.json',
        'sk' => 'Slovak.json',
        'sl' => 'Slovenian.json',
        'sq' => 'Albanian.json',
        'sr' => 'Serbian.json',
        'sv' => 'Swedish.json',
        'sw' => 'Swahili.json',
        'ta' => 'Tamil.json',
        'te' => 'Telugu.json',
        'th' => 'Thai.json',
        'tr' => 'Turkish.json',
        'uk' => 'Ukranian.json',
        'ur' => 'Urdu.json',
        'uz' => 'Uzbek.json',
        'vi' => 'Vietnamese.json',
        'zh' => 'Chinese.json',
    ];
}
