<?php

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

require_once __DIR__ . '/../../../config/config.inc.php';

$finder = new Finder();
$filesystem = new Filesystem();

$scriptDir = __DIR__;
$modulesDir = _PS_MODULE_DIR_;

$moduleTitle = Tools::getValue('title'); // Module Name
$folder = Tools::getValue('folder'); // Module Folder
$prefix = strtolower(Tools::getValue('prefix', 'gc')); // Module Prefix

if (empty($moduleTitle) || !preg_match('/^[a-z]+( [a-z]{3,})?$/i', $moduleTitle)) {
    throw new Exception('Module title is empty or invalid');
}

$originDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR;

if (strpos($moduleTitle, ' ') !== false) {
    list($moduleTitlePre, $moduleTitlePost) = explode(' ', $moduleTitle);
} else {
    $moduleTitlePre = $moduleTitle;
    $moduleTitlePost = '';
}

$moduleTitlePre = ucfirst($moduleTitlePre);
$moduleTitlePost = ucfirst($moduleTitlePost);

$stringFrom = [
    'GC_ModuleTemplate',
    'GcModuleTemplate',
    'ModuleTemplate',
    'gc_moduletemplate',
    'GC Module Template',
    'Module Template',
    'Gcmoduletemplate',
    'moduletemplate',
];

$stringTo = [
    strtoupper($prefix) . '_' . $moduleTitlePre . $moduleTitlePost,
    ucfirst($prefix) . $moduleTitlePre . $moduleTitlePost,
    $moduleTitlePre . $moduleTitlePost,
    $prefix . '_' . strtolower($moduleTitlePre . $moduleTitlePost),
    strtoupper($prefix) . ' ' . $moduleTitlePre . ' ' . $moduleTitlePost,
    $moduleTitlePre . ' ' . $moduleTitlePost,
    ucfirst($prefix) . strtolower($moduleTitlePre . $moduleTitlePost),
    strtolower($moduleTitlePre . $moduleTitlePost),
];

$stringTo = array_map('trim', $stringTo);

$moduleName = $stringTo[3];
$moduleDir = $modulesDir . $folder . $moduleName . DIRECTORY_SEPARATOR;

if ($folder && !is_dir($modulesDir . $folder)) {
    throw new Exception('You have specified a separate folder that did not exists!');
}

if (!$filesystem->exists($moduleDir)) {
    $filesystem->mkdir($moduleDir);
}

$files = $finder->in($originDir)
    ->ignoreDotFiles(false)
    ->ignoreVCS(true)
    ->notName('config_de.xml');

foreach ($files as $file) {
    $sourceFile = $file->getRelativePathname();
    $targetFile = $moduleDir . $sourceFile;

    if ($file->isDir()) {
        if (!$filesystem->exists($targetFile)) {
            $filesystem->mkdir($targetFile);
        }
    }

    if ($file->isFile()) {
        $filesystem->copy($file->getRealPath(), $targetFile);
    }
}

$filesystem->rename($moduleDir . 'gc_moduletemplate.php', $moduleDir . $moduleName . '.php');
$filesystem->rename($moduleDir . 'translations' . DIRECTORY_SEPARATOR . 'de-DE' . DIRECTORY_SEPARATOR . 'ModulesGcmoduletemplateAdmin.de-DE.xlf', $moduleDir . 'translations' . DIRECTORY_SEPARATOR . 'de-DE' . DIRECTORY_SEPARATOR . 'Modules' . $stringTo[6] . 'Admin.de-DE.xlf');
$filesystem->rename($moduleDir . 'translations' . DIRECTORY_SEPARATOR . 'de-DE' . DIRECTORY_SEPARATOR . 'ModulesGcmoduletemplateShop.de-DE.xlf', $moduleDir . 'translations' . DIRECTORY_SEPARATOR . 'de-DE' . DIRECTORY_SEPARATOR . 'Modules' . $stringTo[6] . 'Shop.de-DE.xlf');

$filesystem->remove($moduleDir . 'src/Trait/ExampleTrait.php');

$finder = new Finder();
$finder
    ->in($moduleDir)
    ->ignoreDotFiles(false)
    ->ignoreVCS(true)
    ->notPath(['_dev', '_releases'])
;

foreach ($finder as $file) {
    if (!$file->isFile()) {
        continue;
    }

    $filePath = $file->getRealPath();
    $content = file_get_contents($filePath);

    // Suchen und Ersetzen im Dateinhalt
    $updatedContent = str_replace($stringFrom, $stringTo, $content);

    if ($file->getFilename() === $moduleName . '.php') {
        $updatedContent = preg_replace('/.*ExampleTrait;\r?\n/im', '', $updatedContent);
    }

    file_put_contents($filePath, $updatedContent);
}

shell_exec('cd .. && cd .. && cd ' . $moduleName . ' && composer install --no-dev');
