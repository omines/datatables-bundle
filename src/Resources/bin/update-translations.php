<?php

use Symfony\Component\Intl\Locales;
use Symfony\Component\Yaml\Yaml;

require_once __DIR__.'/autoload.php';

$argc = $_SERVER['argc'];
$argv = $_SERVER['argv'];

if ($argc !== 2 || in_array($argv[1], ['-h', '--help'])) {
    echo '
Usage: php update-translations.php <version>

Updates the translations messages data from https://github.com/DataTables/Plugins

For running this script all vendors must have been installed through composer:

composer install
';
    exit(1);
}

$version = $argv[1];
$zipUrl = sprintf('https://github.com/DataTables/Plugins/archive/%s.zip', $version);

// download and open temp zip file
$tempZipFile = downloadToTempFile($zipUrl);

traverseLangFilesInZip($tempZipFile, function ($filename, $content) {
    $langData = json_decode(substr($content, strpos($content, '{')), true);
    if (!is_array($langData)) {
        printf("JSON error '%s' in %s\n", json_last_error_msg(), $filename);
        return;
    }

    // change keys like sEmptyTable to emptyTable
    $langData = normalizeArrayKeys($langData);

    $localeName = getLocaleNameFromLangFilename($filename);
    $locale = getLocaleByName($localeName);
    if ($locale === false) {
        printf("Unsupported locale name '%s'\n", $localeName);
        return;
    }

    updateMessagesFileWithLangData($locale, $langData);
});

unlink($tempZipFile);

function downloadToTempFile(string $url): string
{
    $tmpfname = tempnam(sys_get_temp_dir(), 'datatables');
    $file = fopen($tmpfname, 'wb+');

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_FAILONERROR => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FILE => $file,
    ]);

    if (!curl_exec($ch)) {
        throw new \RuntimeException('Error: ', curl_error($ch));
    }

    curl_close($ch);
    fclose($file);

    return $tmpfname;
}

function traverseLangFilesInZip(string $zipFile, callable $function)
{
    $zip = new ZipArchive;
    if ($zip->open($zipFile) !== true) {
        throw new \RuntimeException(sprintf('Failed opening "%s"', $zipFile));
    }

    // loop to find lang files
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $filename = $zip->getNameIndex($i);
        if (preg_match('#/i18n/.*\.lang#', $filename)) {
            $content = $zip->getFromIndex($i);
            $function($filename, $content);
        }
    }

    $zip->close();
}

function normalizeArrayKeys(array $input): array
{
    $output = [];
    foreach ($input as $key => $value) {
        if (strpos($key, 's') === 0 || strpos($key, 'o') === 0) {
            $key = lcfirst(substr($key, 1));
        }

        if (is_array($value)) {
            $value = normalizeArrayKeys($value);
        }

        $output[$key] = $value;
    }

    return $output;
}

function updateMessagesFileWithLangData(string $locale, array $langData): bool
{
    $messageFile = sprintf('%s/translations/messages.%s.yml', dirname(__DIR__), $locale);

    $messages = [
        'datatable' => [
            'common' => [],
            'datatable' => [
                'searchPlaceholder' => '',
            ],
        ],
    ];

    if (file_exists($messageFile)) {
        $messages = array_replace_recursive($messages, Yaml::parseFile($messageFile));
    }

    $messages = array_replace_recursive($messages, ['datatable' => ['datatable' => $langData]]);

    printf("Updating messages for %s\n", $locale);
    return file_put_contents($messageFile, Yaml::dump($messages, 4, 4, Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE));
}

function getLocaleNameFromLangFilename($filename)
{
    if (preg_match('#([^/]+)\.lang$#', $filename, $matches)) {
        return ucfirst(str_replace('-', ' ', $matches[1]));
    }

    throw new \RuntimeException(sprintf('Failed parsing locale name from "%s"', $filename));
}

function getLocaleByName($name)
{
    $names = Locales::getNames();
    if ($found = array_search($name, $names, true)) {
        return $found;
    }

    $misspelledLocaleNames = [
        'az' => 'Azerbaijan',
        // 'fil' => 'Filipino', not supported by Symfony Intl
        'nb' => 'Norwegian Bokmal',
        'pt_BR' => 'Portuguese Brasil',
        'sr_Latn' => 'Serbian_latin',
    ];

    if ($found = array_search($name, $misspelledLocaleNames, true)) {
        return $found;
    }

    return false;
}
