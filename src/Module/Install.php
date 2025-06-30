<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Module;

use Monolog\Logger as Monolog;
use PrestaShop\PrestaShop\Adapter\Configuration as ConfigurationAdapterPrestaShop;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;

class Install
{
    private $module;
    private $settings;
    private $logger;

    public function __construct(\PS_Legalcompliance $module, AbstractSettings $settings)
    {
        $this->module = $module;
        $this->settings = $settings;

        $loggerInstance = $this->module->getLogger();
        $loggerInstance->setLevel(
            Monolog::INFO,
            'install'
        );

        $this->logger = $loggerInstance->getInstanceOrCreate('install');
    }

    public function installModule(): bool
    {
        $this->logger->info('Starting installation of module');

        if (\Shop::isFeatureActive()) {
            \Shop::setContext(\Shop::CONTEXT_ALL);
        }

        $this->loadTranslations();

        $this->installSql();
        $this->installHooks();
        $this->installTabs();
        $this->installConfig();
        $this->installOrderStates();
        $this->installFixtures();

        $this->logger->info('Ending installation of module');

        return true;
    }

    public function installSql(): bool
    {
        $sqlQueries = $this->settings->getSqlInstall();

        $this->logger->info(sprintf(
            'Installing %s SQL queries',
            count($sqlQueries)
        ));

        foreach ($sqlQueries as $query) {
            try {
                \Db::getInstance()->execute($query);
            } catch (\Exception $e) {
                $this->logger->error('SQL query failed: ' . $e->getMessage());
            }
        }

        return true;
    }

    public function installHooks(): bool
    {
        $hooks = $this->settings->getHooks();

        $this->logger->info(sprintf(
            'Installing %s hooks',
            count($hooks)
        ));

        foreach ($hooks as $hook) {
            try {
                $this->module->registerHook((string) $hook);
            } catch (\Exception $e) {
                $this->logger->error('Hook installation failed: ' . $e->getMessage());
            }
        }

        return true;
    }

    public function installTabs(): bool
    {
        // Tabs will be installed automaically

        return true;
    }

    public function installConfig(): bool
    {
        try {
            /**
             * @var ConfigurationAdapter $configurationadapter
             */
            $configurationadapter = $this->module->get('onlineshopmodule.module.legalcompliance.configurationadapter');
        } catch (\Exception $e) {
            $configurationadapter = new ConfigurationAdapter(
                $this->module,
                new ConfigurationAdapterPrestaShop(),
                null
            );
        }

        $languages = \Language::getLanguages(false);
        $languageMapping = array_column($languages, 'id_lang', 'iso_code');

        $configs = $this->settings->getConfig();

        $this->logger->info(sprintf(
            'Installing %s configuration settings',
            count($configs)
        ));

        foreach ($configs as $config) {
            $name = $config->getName();
            $value = $config->getValue();
            $withPrefix = $config->usePrefix();

            if (is_array($value)) {
                $mappedValue = [];

                foreach ($languageMapping as $iso => $idLang) { // @phpstan-ignore foreach.emptyArray
                    if (!isset($value[$iso]) && isset($value['en'])) {
                        $mappedValue[$idLang] = $value['en'];
                    } elseif (!isset($value[$iso]) && isset($value[0])) {
                        $mappedValue[$idLang] = $value[0];
                    } else {
                        $mappedValue[$idLang] = $value[$iso];
                    }
                }

                $value = $mappedValue;
            }

            if ($config->isGlobal()) {
                $result = $configurationadapter->setGlobal($name, $value, $config->isHtml(), $withPrefix);
            } else {
                $result = $configurationadapter->set($name, $value, $config->isHtml(), null, $withPrefix);
            }

            if (!$result) {
                $this->logger->error(sprintf(
                    'Configuration setting "%s" could not be installed',
                    $name
                ));
            }
        }

        return true;
    }

    public function installOrderStates(): bool
    {
        $orderStates = $this->settings->getOrderStates();
        $config = $this->module->getConfig();

        $this->logger->info(sprintf(
            'Installing %s order states',
            count($orderStates)
        ));

        foreach ($orderStates as $orderState) {
            $id = $orderState->getId();

            if (
                $id == 'new_order'
                && $newOrderStateId = (int) \Configuration::get('OS_NEWORDER')
            ) {
                $newOrderState = new \OrderState($newOrderStateId);

                if (\Validate::isLoadedObject($newOrderState)) {
                    $config->set('OS_NEWORDER', (int) $newOrderState->id);

                    $this->logger->info(sprintf(
                        'Order state "%s" already exists with ID %d, skipping installation',
                        $id,
                        $newOrderState->id
                    ));

                    continue;
                }
            }

            $object = $orderState->getObject();

            if (!$object->save()) {
                $this->logger->error(sprintf(
                    'Order state "%s" could not be installed',
                    $id
                ));

                continue;
            }

            if (
                is_file($this->module->getLocalPath() . 'views/img/os.gif')
                && !@copy(
                    $this->module->getLocalPath() . 'views/img/os.gif',
                    _PS_IMG_DIR_ . 'os/' . (int) $object->id . '.gif'
                )
            ) {
                $this->logger->error(sprintf(
                    'Order state "%s" icon could not be copied',
                    $id
                ));
            }

            $mails = [];

            foreach ($object->template as $idLang => $template) {
                $isoCode = \Language::getIsoById($idLang);

                if (!$isoCode) {
                    continue;
                }

                foreach (['html', 'txt'] as $fileEnding) {
                    $filename = $this->module->getLocalPath() . 'mails/' . $isoCode . '/' . $template . '.' . $fileEnding;

                    if (is_file($filename)) {
                        if (!@copy(
                            $filename,
                            _PS_MAIL_DIR_ . $isoCode . '/' . $template . '.' . $fileEnding
                        )) {
                            $this->logger->error(sprintf(
                                'Order state "%s" email template "%s.%s" could not be copied',
                                $id,
                                $template,
                                $fileEnding
                            ));
                        }

                        $mails[] = $template;
                    }
                }
            }

            $mails = array_unique($mails);

            $moduleManagerBuilder = ModuleManagerBuilder::getInstance();
            $moduleManager = $moduleManagerBuilder->build();

            if (
                !empty($mails)
                && $moduleManager->isInstalled('ps_legalcompliance')
            ) {
                require_once _PS_MODULE_DIR_ . 'ps_legalcompliance/entities/AeucEmailEntity.php';

                foreach ($mails as $template => $isoFiles) {
                    /**
                     * @var \AeucEmailEntity $newEmail
                     */
                    $newEmail = new \AeucEmailEntity();
                    $newEmail->filename = $template;
                    $newEmail->display_name = $this->module->displayName;
                    $newEmail->save();
                }
            }

            if ($id == 'awaiting_payment') {
                $config->set('OS', (int) $object->id);
                $config->set('AWAITING_PAYMENT', 1);
            } elseif ($id == 'new_order') {
                \Configuration::updateValue('OS_NEWORDER', (int) $object->id);
                $config->set('OS_NEWORDER', (int) $object->id);
            }
        }

        return true;
    }

    public function installFixtures(): bool
    {
        $callable = $this->settings->getFixtures();

        try {
            return $callable();
        } catch (\Exception $e) {
            $this->logger->error('Fixtures installation failed: ' . $e->getMessage());

            return false;
        }
    }

    public function uninstallModule(): bool
    {
        $this->logger->info('Starting uninstallation of module');

        $this->uninstallFixtures();
        $this->uninstallOrderStates();
        $this->uninstallConfig();
        $this->uninstallTabs();
        $this->uninstallHooks();
        $this->uninstallSql();

        $this->logger->info('Ending uninstallation of module');

        return true;
    }

    public function uninstallSql(): bool
    {
        $sqlQueries = $this->settings->getSqlUninstall();

        foreach ($sqlQueries as $query) {
            try {
                \Db::getInstance()->execute($query);
            } catch (\PrestaShopException $e) {
                // Table not found;
                continue;
            } catch (\PDOException $e) {
                // SQL syntax error
                continue;
            }
        }

        return true;
    }

    public function uninstallHooks(): bool
    {
        // Hooks will be uninstalled automatically

        return true;
    }

    public function uninstallTabs(): bool
    {
        // Tabs will be uninstalled automatically

        return true;
    }

    public function uninstallConfig(): bool
    {
        /**
         * @var ConfigurationAdapter $configurationadapter
         */
        $configurationadapter = $this->module->get('onlineshopmodule.module.legalcompliance.configurationadapter');

        foreach ($this->settings->getConfig() as $config) {
            if (!$config->canUninstall()) {
                continue;
            }

            $configurationadapter->delete($config->getName());
        }

        return true;
    }

    public function uninstallOrderStates(): bool
    {
        $orderStateIds = \Db::getInstance()->executeS('
            SELECT `id_order_state`
            FROM `' . _DB_PREFIX_ . 'order_state`
            WHERE `module_name` = \'' . pSQL($this->module->name) . '\'
            AND `id_order_state` != ' . (int) \Configuration::get('OS_NEWORDER') . '
            AND `deleted` = 0
        ');

        foreach ($orderStateIds as $idOrderState) {
            $orderState = new \OrderState($idOrderState);
            $orderState->deleted = true;
            $orderState->save();
        }

        return true;
    }

    public function uninstallFixtures(): bool
    {
        return true;
    }

    public function resetModule(): bool
    {
        $this->uninstallHooks();
        $this->uninstallTabs();

        $this->installHooks();
        $this->installTabs();

        return true;
    }

    protected function loadTranslations(): bool
    {
        (new TranslationLoader(
            $this->module->getTranslator(),
            $this->settings->getTranslations()
        ))->load();

        return true;
    }
}
