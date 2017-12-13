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
            new \Twig_SimpleFunction('datatable_settings', function (DataTable $dataTable) {
                return json_encode([
                    'name' => $dataTable->getName(),
                    'method' => $dataTable->getMethod(),
                    'state' => $dataTable->getPersistState(),
                    'options' => [
                        'language' => $this->getLanguageSettings($dataTable),
                    ],
                ]);
            }, ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param DataTable $dataTable
     * @return array
     */
    private function getLanguageSettings(DataTable $dataTable)
    {
        $locale = $this->translator->getLocale();
        if ($dataTable->isLanguageFromCDN() && array_key_exists($locale, self::LANGUAGES_IN_CDN)) {
            return ['url' => '//cdn.datatables.net/plug-ins/1.10.15/i18n/' . self::LANGUAGES_IN_CDN[$locale]];
        } else {
            $domain = $dataTable->getTranslationDomain();

            return [
                'processing' => $this->translator->trans('datatable.datatable.processing', [], $domain),
                'search' => $this->translator->trans('datatable.datatable.search', [], $domain),
                'lengthMenu' => $this->translator->trans('datatable.datatable.lengthMenu', [], $domain),
                'info' => $this->translator->trans('datatable.datatable.info', [], $domain),
                'infoEmpty' => $this->translator->trans('datatable.datatable.infoEmpty', [], $domain),
                'infoFiltered' => $this->translator->trans('datatable.datatable.infoFiltered', [], $domain),
                'infoPostFix' => $this->translator->trans('datatable.datatable.infoPostFix', [], $domain),
                'loadingRecords' => $this->translator->trans('datatable.datatable.loadingRecords', [], $domain),
                'zeroRecords' => $this->translator->trans('datatable.datatable.zeroRecords', [], $domain),
                'emptyTable' => $this->translator->trans('datatable.datatable.emptyTable', [], $domain),
                'searchPlaceholder' => $this->translator->trans('datatable.datatable.searchPlaceholder', [], $domain),
                'paginate' => [
                    'first' => $this->translator->trans('datatable.datatable.paginate.first', [], $domain),
                    'previous' => $this->translator->trans('datatable.datatable.paginate.previous', [], $domain),
                    'next' => $this->translator->trans('datatable.datatable.paginate.next', [], $domain),
                    'last' => $this->translator->trans('datatable.datatable.paginate.last', [], $domain),
                ],
                'aria' => [
                    'sortAscending' => $this->translator->trans('datatable.datatable.aria.sortAscending', [], $domain),
                    'sortDescending' => $this->translator->trans('datatable.datatable.aria.sortDescending', [], $domain),
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

    const LANGUAGES_IN_CDN = [
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
