<?php

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

require_once __DIR__ . '/../../../config/config.inc.php';

$finder = new Finder();
$filesystem = new Filesystem();
$zip = new ZipArchive();

$releaseVersion = Tools::getValue('version'); // Module Name

if (empty($releaseVersion) || !preg_match('/^(?<major>\d+)(?:\.(?<minor>\d+))?(?:\.(?<patch>\d+))?(-(?<release>a|b|alpha|beta|rc)((?:\.)?(?<release_number>\d+))?)?$/i', $releaseVersion)) {
    throw new Exception('Release version is empty or invalid. Example: \'version=1.0.0\'');
}

$moduleDir = realpath(__DIR__ . DIRECTORY_SEPARATOR . '..') . DIRECTORY_SEPARATOR;
$releasesDir = $moduleDir . '_releases' . DIRECTORY_SEPARATOR;
$releaseDir = $releasesDir . $releaseVersion . DIRECTORY_SEPARATOR;
$moduleName = basename($moduleDir);

if (!$filesystem->exists($releaseDir)) {
    $filesystem->mkdir($releaseDir);
}

$files = $finder
    ->in($moduleDir)
    ->ignoreDotFiles(false)
    ->ignoreVCS(true)
    ->notPath([
        '_dev',
        'tests',
        '_releases',
    ])
    ->notName([
        'config_de.xml',
        '.gitignore',
        '.gitattributes',
        '.php-cs-fixer.dist.php',
        'license_header.txt',
        'phpstan.bat',
    ])
;

foreach ($files as $file) {
    $sourceFile = $file->getRelativePathname();
    $targetFile = $releaseDir . $sourceFile;

    if ($file->isDir()) {
        if (!$filesystem->exists($targetFile)) {
            $filesystem->mkdir($targetFile);
        }
    }

    if ($file->isFile()) {
        $filesystem->copy($file->getRealPath(), $targetFile);
    }
}

shell_exec('cd ' . $releaseDir . ' && composer install --no-dev');

$filesystem->remove($releaseDir . 'composer.json');
$filesystem->remove($releaseDir . 'composer.lock');

$zipName = $moduleName . '_' . $releaseVersion . '.zip';
$zipPath = $releasesDir . $zipName;

if ($zip->open($zipPath, ZipArchive::CREATE) !== true) {
    throw new Exception('Cannot open zip file');
}

$finder = new Finder();
$files = $finder->in($releaseDir)
    ->ignoreDotFiles(false)
    ->ignoreVCS(true)
;

foreach ($files as $file) {
    if ($file->isDir()) {
        continue;
    }

    $filePath = $file->getRealPath();
    $relativePath = substr($filePath, strlen($releaseDir));

    $zip->addFile($filePath, $relativePath);
}

$zip->close();
