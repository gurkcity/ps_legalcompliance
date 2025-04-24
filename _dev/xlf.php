<?php

/**
 * Dieses Script holt alle neuen Übersetungen vom GC Moduletemplate in die
 * Übersetzungen dieses Moduls.
 * Dazu die Datein translations/de-DE/ModulesGcmoduletemplateAdmin.de-De.xlf
 * und translations/de-DE/ModulesGcmoduletemplateFront.de-De.xlf in vom GC
 * Moduletemplate rüber kopieren und dieses Script einmal durchlaufen lassen.
 */

require_once __DIR__ . '/../../../config/config.inc.php';

$module_name = basename(realpath(__DIR__ . '/../'));
$module_name = ucfirst(str_replace('_', '', $module_name));

$files = [
    __DIR__ . '/../translations/de-DE/ModulesGcmoduletemplateAdmin.de-De.xlf' => __DIR__ . '/../translations/de-DE/Modules' . $module_name . 'Admin.de-De.xlf',
    __DIR__ . '/../translations/de-DE/ModulesGcmoduletemplateShop.de-De.xlf' => __DIR__ . '/../translations/de-DE/Modules' . $module_name . 'Shop.de-De.xlf',
];

foreach ($files as $from => $to) {
    if (copyXML($from, $to)) {
        unlink($from);
    }
}

function copyXML($from, $to)
{
    if (!is_file($from)) {
        return false;
    }

    $content_to = '<?xml version="1.0" encoding="UTF-8"?>
    <xliff xmlns="urn:oasis:names:tc:xliff:document:1.2" version="1.2">
      <file original="admin-dev" source-language="de-DE" target-language="de-DE" datatype="plaintext">
        <body>
        </body>
      </file>
    </xliff>';

    if (is_file($to)) {
        $content_to = file_get_contents($to);
    }

    $xlr_module = new ExSimpleXMLElement($content_to);
    $xlr_template = new ExSimpleXMLElement(file_get_contents($from));

    $module_ids = [];

    foreach ($xlr_module->file->body->{'trans-unit'} as $trans_unit) {
        foreach ($trans_unit->attributes() as $id => $value) {
            if ($id != 'id') {
                continue;
            }

            $module_ids[] = (string) $value;
        }
    }

    foreach ($xlr_template->file->body->{'trans-unit'} as $trans_unit) {
        foreach ($trans_unit->attributes() as $id => $value) {
            if ($id != 'id') {
                continue;
            }

            if (in_array((string) $value, $module_ids)) {
                continue;
            }

            $xlr_module->file->body->appendXML($trans_unit);
        }
    }

    $xlr_module->asXML($to);

    return true;
}

class ExSimpleXMLElement extends SimpleXMLElement
{
    public function appendXML($append)
    {
        if ($append) {
            if (strlen(trim((string) $append)) == 0) {
                $xml = $this->addChild($append->getName());

                foreach ($append->children() as $child) {
                    $xml->appendXML($child);
                }
            } else {
                $xml = $this->addChild($append->getName(), (string) $append);
            }
            foreach ($append->attributes() as $n => $v) {
                $xml->addAttribute($n, $v);
            }
        }
    }
}
