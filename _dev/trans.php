<?php

/**
 * Documentation for translations (example autostatus module)
 *
 * Preparation:
 * 1. Replace
 *     - "->l('abcdef')" ... "->trans('abcdef', [], 'domain')"
 *     - "{l s='abcdef' mod='modulname'}" ... "{l s='abcdef' d='domain'}"
 *     - "sprintf($this->l('a string %s abcdef'), $variable)" ... $this->trans('a string %module% abcdef', ['%module%' => $variable], 'Modules.Gcautostatus.Admin')
 *
 * Regex:
 * ->l\('(.*)'\)
 * ->trans('$1', [], 'Modules.Gcmoduletemplate.Admin')
 *
 * {l s='(.*)' mod='gc_autostatus'}
 * {l s='$1' d='Modules.Gcmoduletemplate.Admin'}
 *
 * domain = Modules.Modulname.Admin Example: Modules.Gcmoduletemplate.Admin
 * domain = Modules.Modulname.Shop Example: Modules.Gcmoduletemplate.Shop
 * https://devdocs.prestashop-project.org/8/development/internationalization/translation/translation-domains/#modules
 *
 * Übersetzungen anlegen
 * 1. Modul installieren
 * 2. Admin -> International -> Übersetzungen -> Übersetzungen exportieren
 *    - Deutsch
 *    - "Installed Module translations"
 * 3. Ordner 'de-DE' aus Zip Datei in module /translations/ 1 zu 1 kopieren (Dateien ersetzen)
 *    Wenn hier mehr als die Admin und Front Datei drin sind, sind nicht alle Domains korrekt angepasst worden (siehe Vorbereitungen)!
 * 4. dieses Script ausführen (schreibt die Einträge in translation Tabelle in DB)
 * 5. Admin -> Erweiterte Einstellungen -> Leistung -> Cache löschen
 * 6. Admin -> International -> Übersetzungen -> Übersetzungen ändern (optional)
 *     - Installierte Module
 *     - Deutsch
 * 7. Übersetzungen kontrollieren und ergänzen
 * 8. Schritt 5, 2 und 3 nochmals ausführen
 * 9. /translations/de.php löschen
 * 10. Änderungen in Git committen
 */
require_once __DIR__ . '/../../../config/config.inc.php';

$regex_php = '/trans\(\'(.*)\',\s\[.*\],\s\'(.*)\'\)/i';
$regex_tpl = '/\{l\ss=\'(.*)\'\sd=\'(.*)\'}/i';

$dir_iterator = new RecursiveDirectoryIterator(__DIR__ . '/..');
$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);

if (!is_file(__DIR__ . '/../translations/de.php')) {
    echo 'de.php not found';
    exit;
}

require_once __DIR__ . '/../translations/de.php';

$translations = [];

foreach ($_MODULE as $key => $row) {
    $m = [];

    if (!preg_match('/_([0-9a-z]+)$/i', $key, $m)) {
        continue;
    }

    $translations[$m[1]] = $row;
}

$final_translations = [];

foreach ($iterator as $file) {
    $ext = $file->getExtension();

    if (!in_array($ext, ['php', 'tpl'])) {
        continue;
    }

    $file_contents = file_get_contents($file->getPathname());

    $m = [];

    if ($ext == 'php') {
        $regex = $regex_php;
    } elseif ($ext == 'tpl') {
        $regex = $regex_tpl;
    }

    if (!preg_match_all($regex, $file_contents, $m, PREG_SET_ORDER)) {
        continue;
    }

    if (empty($m)) {
        continue;
    }

    foreach ($m as $row) {
        $md5 = md5($row['1']);
        $domain = str_replace('.', '', $row['2']);

        if (!isset($translations[$md5])) {
            continue;
        }

        $final_translations[] = [
            'from' => $row['1'],
            'to' => $translations[$md5],
            'domain' => $domain,
        ];
    }
}

if (!$final_translations) {
    echo 'translations not found';
    exit;
}

$insert = [];

foreach ($final_translations as $row) {
    $insert[] = '(1, \'' . pSql($row['from']) . '\', \'' . pSql($row['to']) . '\', \'' . pSql($row['domain']) . '\')';
}

Db::getInstance()->execute('
    INSERT IGNORE INTO `' . _DB_PREFIX_ . 'translation` (`id_lang`, `key`, `translation`, `domain`) VALUES
    ' . implode(',', $insert) . '
');
