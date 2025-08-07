<?php

use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

require_once __DIR__ . '/../../../config/config.inc.php';

$finder = new Finder();
$filesystem = new Filesystem();

$scriptDir = __DIR__;
$modulesDir = _PS_MODULE_DIR_;

$moduleTitle = Tools::getValue('title'); // Module Name
$prefix = strtolower(Tools::getValue('prefix', 'gc')); // Module Prefix
$isPayment = (bool) Tools::getValue('payment', false); // Is Payment Module
$hasCron = (bool) Tools::getValue('cron', false); // Module has cron
$needExample = (bool) Tools::getValue('example', false); // Module need example
$hasConfig = (bool) Tools::getValue('config', true); // Module has config

$folder = Tools::getValue('folder'); // Module Folder

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

// Create the module directory if it does not exist
if (!$filesystem->exists($moduleDir)) {
    $filesystem->mkdir($moduleDir);
}

// Copy files from the origin directory to the module directory
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

// Rename and remove common files and directories
$filesystem->rename($moduleDir . 'gc_moduletemplate.php', $moduleDir . $moduleName . '.php');
$filesystem->rename($moduleDir . 'translations' . DIRECTORY_SEPARATOR . 'de-DE' . DIRECTORY_SEPARATOR . 'ModulesGcmoduletemplateAdmin.de-DE.xlf', $moduleDir . 'translations' . DIRECTORY_SEPARATOR . 'de-DE' . DIRECTORY_SEPARATOR . 'Modules' . $stringTo[6] . 'Admin.de-DE.xlf');
$filesystem->rename($moduleDir . 'translations' . DIRECTORY_SEPARATOR . 'de-DE' . DIRECTORY_SEPARATOR . 'ModulesGcmoduletemplateShop.de-DE.xlf', $moduleDir . 'translations' . DIRECTORY_SEPARATOR . 'de-DE' . DIRECTORY_SEPARATOR . 'Modules' . $stringTo[6] . 'Shop.de-DE.xlf');
$filesystem->rename($moduleDir . 'mails/de/gc_moduletemplate.html', $moduleDir . 'mails/de/' . $moduleName . '.html');
$filesystem->rename($moduleDir . 'mails/de/gc_moduletemplate.txt', $moduleDir . 'mails/de/' . $moduleName . '.txt');
$filesystem->rename($moduleDir . 'mails/de/gc_moduletemplate_payment.html', $moduleDir . 'mails/de/' . $moduleName . '_payment.html');
$filesystem->rename($moduleDir . 'mails/de/gc_moduletemplate_payment.txt', $moduleDir . 'mails/de/' . $moduleName . '_payment.txt');
$filesystem->rename($moduleDir . 'mails/themes/modern/gc_moduletemplate.html.twig', $moduleDir . 'mails/themes/modern/' . $moduleName . '.html.twig');
$filesystem->rename($moduleDir . 'mails/themes/modern/gc_moduletemplate_payment.html.twig', $moduleDir . 'mails/themes/modern/' . $moduleName . '_payment.html.twig');

$filesystem->remove($moduleDir . 'vendor/autoload.php');
$filesystem->remove($moduleDir . 'composer.lock');

// Remove Payment
if (!$isPayment) {
    $filesystem->remove($moduleDir . 'controllers/front/payment.php');
    $filesystem->remove($moduleDir . 'mails/de/gc_moduletemplate_payment.html');
    $filesystem->remove($moduleDir . 'mails/de/gc_moduletemplate_payment.txt');
    $filesystem->remove($moduleDir . 'mails/themes/modern/gc_moduletemplate_payment.html.twig');
    $filesystem->remove($moduleDir . 'src/Controller/PaymentAdminController.php');
    $filesystem->remove($moduleDir . 'src/Form/DataProvider/PaymentFormDataProvider.php');
    $filesystem->remove($moduleDir . 'src/Form/Type/PaymentType.php');
    $filesystem->remove($moduleDir . 'src/Payment/PaymentLogo.php');
    $filesystem->remove($moduleDir . 'src/Payment/PaymentLogoFactory.php');
    $filesystem->remove($moduleDir . 'src/Traits/ModulePaymentTrait.php');
    $filesystem->remove($moduleDir . 'views/img/payment_orig.png');
    $filesystem->remove($moduleDir . 'views/templates/admin/payment.html.twig');
    $filesystem->remove($moduleDir . 'views/templates/front/payment_infos.tpl');
    $filesystem->remove($moduleDir . 'views/templates/front/payment.tpl');
    $filesystem->remove($moduleDir . 'views/templates/hook/payment_return.tpl');
} else {

}

// Remove Cron
if (!$hasCron) {
    $filesystem->remove($moduleDir . 'controllers/front/cron.php');
    $filesystem->remove($moduleDir . 'src/Controller/CronAdminController.php');
    $filesystem->remove($moduleDir . 'src/Cron/CronExecuter.php');
    $filesystem->remove($moduleDir . 'src/Cron/CronPresenter.php');
    $filesystem->remove($moduleDir . 'src/Cron/CronQueueRepository.php');
    $filesystem->remove($moduleDir . 'src/Exception/CronExeption.php');
    $filesystem->remove($moduleDir . 'src/Form/DataProvider/CronFormDataProvider.php');
    $filesystem->remove($moduleDir . 'src/Form/Type/CronType.php');
    $filesystem->remove($moduleDir . 'src/Response/Cron.php');
    $filesystem->remove($moduleDir . 'views/templates/admin/cron/cron.html.twig');
}

// Remove Example
if (!$needExample) {
    $filesystem->remove($moduleDir . 'config/_examplecommand.yml');
    $filesystem->remove($moduleDir . 'config/_exampleform.yml');
    $filesystem->remove($moduleDir . 'config/_examplegrid.yml');
    $filesystem->remove($moduleDir . 'config/_examplegridroutes.yml');
    $filesystem->remove($moduleDir . 'src/Command/ExampleCommand.php');
    $filesystem->remove($moduleDir . 'src/Controller/ExampleAdminController.php');
    $filesystem->remove($moduleDir . 'src/Form/Type/ExampleType.php');
    $filesystem->remove($moduleDir . 'src/Grid/Example');
    $filesystem->remove($moduleDir . 'src/Model/Example.php');
    $filesystem->remove($moduleDir . 'src/Traits/ExampleTrait.php');
    $filesystem->remove($moduleDir . 'views/templates/admin/exampleform.html.twig');
    $filesystem->remove($moduleDir . 'views/templates/admin/examplegrid.html.twig');
}

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

    // Rename file content
    $updatedContent = str_replace($stringFrom, $stringTo, $content);

    // Remove payment strings
    if (!$isPayment) {
        $updatedContent = preg_replace('/# ~~payment_start~~.*?# ~~payment_end~~\r?\n/s', '', $updatedContent);
    } else {
        $updatedContent = preg_replace('/# ~~payment_start~~\r?\n/s', '', $updatedContent);
        $updatedContent = preg_replace('/# ~~payment_end~~\r?\n/s', '', $updatedContent);

        if ($file->getFilename() === $moduleName . '.php') {
            $updatedContent = preg_replace('/extends Module/i', 'extends PaymentModule', $updatedContent);
        }
    }

    // Remove cron strings
    if (!$hasCron) {
        $updatedContent = preg_replace('/# ~~cron_start~~.*?# ~~cron_end~~\r?\n/s', '', $updatedContent);
    } else {
        $updatedContent = preg_replace('/# ~~cron_start~~\r?\n/s', '', $updatedContent);
        $updatedContent = preg_replace('/# ~~cron_end~~\r?\n/s', '', $updatedContent);
    }

    // Remove example strings
    if (!$needExample) {
        $updatedContent = preg_replace('/# ~~example_start~~.*?# ~~example_end~~\r?\n/s', '', $updatedContent);
    } else {
        $updatedContent = preg_replace('/# ~~example_start~~\r?\n/s', '', $updatedContent);
        $updatedContent = preg_replace('/# ~~example_end~~\r?\n/s', '', $updatedContent);
    }

    file_put_contents($filePath, $updatedContent);
}

shell_exec('cd .. && cd .. && cd ' . $moduleName . ' && composer install --no-dev');
