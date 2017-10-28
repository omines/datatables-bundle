<?php

/*
 * Symfony DataTables Bundle
 * (c) Omines Internetbureau B.V. - https://omines.nl/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Omines\DatatablesBundle\Twig;

use Omines\DatatablesBundle\Column\AbstractColumn;
use Omines\DatatablesBundle\Datatable;
use Symfony\Component\Translation\TranslatorInterface;

class DatatablesExtension extends \Twig_Extension
{
    protected $translator;

    public function __construct(TranslatorInterface $translator)
    {
        $this->translator = $translator;
    }

    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('datatable', [$this, 'datatable'],
                ['is_safe' => ['html'], 'needs_environment' => true]),
            new \Twig_SimpleFunction('datatable_html', [$this, 'datatableHtml'],
                ['is_safe' => ['html'], 'needs_environment' => true]),
            new \Twig_SimpleFunction('datatable_js', [$this, 'datatableJs'],
                ['is_safe' => ['html'], 'needs_environment' => true]),
        ];
    }

    public function datatable(\Twig_Environment $twig, Datatable $datatable, $options = [])
    {
        return $twig->render('@Datatables/datatable.html.twig', [
            'datatable' => $datatable,
            'options' => $this->getOptions($datatable, $options),
        ]);
    }

    public function datatableHtml(\Twig_Environment $twig, Datatable $datatable)
    {
        return $twig->render('@Datatables/datatable_html.html.twig', [
            'datatable' => $datatable,
        ]);
    }

    public function datatableJs(\Twig_Environment $twig, Datatable $datatable, $options = [])
    {
        return $twig->render('@Datatables/datatable_js.html.twig', [
            'datatable' => $datatable,
            'options' => $this->getOptions($datatable, $options),
        ]);
    }

    private function getOptions(Datatable $datatable, $options)
    {
        $locale = $this->translator->getLocale();
        $result = array_merge($datatable->getOptions(), $options);

        $result['columns'] = array_map(
            function (AbstractColumn $column) {
                return [
                    'data' => $column->getName(),
                    'orderable' => $column->isOrderable(),
                    'searchable' => $column->isSearchable(),
                    'visible' => $column->isVisible(),
                    'className' => $column->getClass(),
                ];
            }, $datatable->getState()->getColumns());

        if ($datatable->getSetting('languageFromCdn') && array_key_exists($locale, $this->languageCDNFile)) {
            $result['language'] = ['url' => "//cdn.datatables.net/plug-ins/1.10.15/i18n/{$this->languageCDNFile[$locale]}"];
        } else {
            $result['language'] = [
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
                ], ];
        }

        return $result;
    }

    /**
     * Returns the name of the extension.
     *
     * @return string The extension name
     */
    public function getName()
    {
        return 'DatatablesBundle';
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
