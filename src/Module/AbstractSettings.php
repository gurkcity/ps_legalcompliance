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

use Doctrine\DBAL\Connection;
use Monolog\Logger;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Cron\CronQueueRepository;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\Config;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\Controller;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\Hook;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\Orderstate;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\Sql;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\Tab;
use OrderState as PS_OrderState;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeCatalogInterface;

abstract class AbstractSettings
{
    protected $connection;
    protected $dbPrefix = '';
    protected $module;
    protected $translator;

    public function __construct(
        \PS_Legalcompliance $module,
        Connection $connection,
        string $dbPrefix
    ) {
        $this->module = $module;
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
        $this->translator = $this->module->getTranslator();
    }

    abstract public function config(): array;

    abstract public function controllers(): array;

    abstract public function cron(): array;

    abstract public function hooks(): array;

    abstract public function orderStates(): array;

    abstract public function sql(): Sql;

    abstract public function tabs(): array;

    abstract public function translations(): array;

    abstract public function fixtures(): callable;

    public function getConfig(): array
    {
        $config = $this->config();

        $config[] = new Config('LICENSE', '', true);
        $config[] = new Config('PRIVACY', 0, true);
        $config[] = new Config('LOG_LEVEL', Logger::WARNING, true);

        if ($this->module->isPayment()) {
            $config[] = new Config('SHOW_PAYMENT_LOGO', true);
            $config[] = new Config('PAYMENT_LOGO', '');
        }

        if ($this->module->hasCronjobs()) {
            $config[] = new Config('CRON_MAINTENANCE', false);
            $config[] = new Config('CRON_ROWS_PER_RUN', 1);
        }

        return $config;
    }

    public function getControllers(): array
    {
        $controllers = $this->controllers();

        $additionalControllers = [];

        if ($this->module->isPayment()) {
            $additionalControllers[] = new Controller('payment');
        }

        if ($this->module->hasCronjobs()) {
            $additionalControllers[] = new Controller('cron');
        }

        if (is_file($this->module->getLocalPath() . 'controllers/front/ajax.php')) {
            $additionalControllers[] = new Controller('ajax');
        }

        $controllers = array_merge($controllers, $additionalControllers);

        $controllers = $this->convertToInstance(Controller::class, $controllers);

        return $controllers;
    }

    public function getCron(): array
    {
        return $this->cron();
    }

    public function isUsingCron(): bool
    {
        return !empty($this->getCron());
    }

    public function isCronUsingQueue(): bool
    {
        foreach ($this->getCron() as $methodName => $cron) {
            if (!empty($cron['use_queue'])) {
                return true;
            }
        }

        return false;
    }

    public function getHooks(): array
    {
        $hooks = $this->hooks();

        $hooks[] = new Hook('actionAdminControllerSetMedia');

        if (
            is_file(_PS_MODULE_DIR_ . $this->module->name . '/views/css/front/module.css')
            || is_file(_PS_MODULE_DIR_ . $this->module->name . '/views/js/front/module.js')
            || is_file(_PS_MODULE_DIR_ . $this->module->name . '/views/css/front/module_' . $this->module->version . '.css')
            || is_file(_PS_MODULE_DIR_ . $this->module->name . '/views/js/front/module_' . $this->module->version . '.js')
        ) {
            $hooks[] = new Hook('actionFrontControllerSetMedia');
        }

        if ($this->module->isPayment()) {
            $hooks[] = new Hook('paymentOptions');
            $hooks[] = new Hook('displayPaymentReturn');
            $hooks[] = new Hook('actionGetExtraMailTemplateVars');
            $hooks[] = new Hook('actionEmailSendBefore');
        }

        if (
            is_dir(_PS_MODULE_DIR_ . $this->module->name . '/mails/themes/modern')
            || is_dir(_PS_MODULE_DIR_ . $this->module->name . '/mails/themes/classic')
        ) {
            $hooks[] = new Hook(ThemeCatalogInterface::LIST_MAIL_THEMES_HOOK);
        }

        return $hooks;
    }

    public function getOrderStates(): array
    {
        if (
            !$this->module->isPayment()
            || !$this->module->installOrderStates
        ) {
            return $this->orderStates();
        }

        $config = $this->module->getConfig();

        $idOrderState = (int) $config->get('OS');

        $osObject = new PS_OrderState($idOrderState);

        if (!\Validate::isLoadedObject($osObject)) {
            $osObject->name = [];
            $osObject->template = [];

            $sendEmail = false;

            foreach (\Language::getLanguages() as $language) {
                $osObject->name[$language['id_lang']] = $this->translator->trans('Awaiting payment: Legalcompliance', [], 'Modules.Pslegalcompliance.Admin');

                if (is_file($this->module->getLocalPath() . 'mails/' . $language['iso_code'] . '/' . $this->module->name . '_payment.html')) {
                    $osObject->template[$language['id_lang']] = $this->module->name . '_payment';

                    $sendEmail = true;
                }
            }

            $osObject->send_email = $sendEmail;
            $osObject->module_name = $this->module->name;
            $osObject->invoice = false;
            $osObject->color = '#4169e1';
            $osObject->unremovable = false;
            $osObject->logable = false;
            $osObject->delivery = false;
            $osObject->hidden = false;
            $osObject->shipped = false;
            $osObject->paid = false;
            $osObject->deleted = false;
            $osObject->pdf_invoice = false;
            $osObject->pdf_delivery = false;
        }

        $orderStates['awaiting_payment'] = new Orderstate(
            $this->translator->trans('Awaiting Payment', [], 'Modules.Pslegalcompliance.Admin'),
            'awaiting_payment',
            $osObject
        );

        $idOrderState = (int) \Configuration::get('OS_NEWORDER');

        $osObject = new PS_OrderState($idOrderState);

        if (!\Validate::isLoadedObject($osObject)) {
            $osObject->name = [];
            $osObject->template = [];

            foreach (\Language::getLanguages() as $language) {
                $osObject->name[$language['id_lang']] = $this->translator->trans('Order placed', [], 'Modules.Pslegalcompliance.Shop');
            }

            $osObject->send_email = false;
            $osObject->module_name = '';
            $osObject->invoice = false;
            $osObject->color = '#FF8C00';
            $osObject->unremovable = true;
            $osObject->logable = false;
            $osObject->delivery = false;
            $osObject->hidden = true;
            $osObject->shipped = false;
            $osObject->paid = false;
            $osObject->deleted = false;
            $osObject->pdf_invoice = false;
            $osObject->pdf_delivery = false;
        }

        $orderStates['new_order'] = new Orderstate(
            $this->translator->trans('Order placed', [], 'Modules.Pslegalcompliance.Admin'),
            'new_order',
            $osObject
        );

        if ($parentOrderStates = $this->orderStates()) {
            $orderStates = array_merge($orderStates, $parentOrderStates);
        }

        return $orderStates;
    }

    public function getSql(): Sql
    {
        $sql = $this->sql();
        $schema = $sql->getSchema();

        if ($this->isCronUsingQueue()) {
            try {
                /**
                 * @var CronQueueRepository $cronQueueRepository
                 */
                $cronQueueRepository = $this->module->get('onlineshopmodule.module.legalcompliance.cronqueuerepository');
            } catch (\Throwable $e) {
                $cronQueueRepository = new CronQueueRepository(
                    $this->module->get('doctrine.dbal.default_connection'),
                    $this->dbPrefix,
                    $this->module
                );
            }

            $table = $schema->createTable($cronQueueRepository->getTableName());
            $table->addColumn('id_cron_queue', 'integer', ['unsigned' => true, 'autoincrement' => true]);
            $table->addColumn('value', 'string', ['length' => 128]);
            $table->addColumn('type', 'string', ['length' => 32, 'notnull' => false]);
            $table->addColumn('date_add', 'datetime');
            $table->addColumn('executed', 'integer', ['default' => 0]);
            $table->addColumn('runtime', 'integer', ['unsigned' => true, 'default' => 0]);
            $table->addColumn('priority', 'integer', ['unsigned' => true, 'default' => 1]);
            $table->setPrimaryKey(['id_cron_queue']);
            $table->addIndex(['executed']);
            $table->addIndex(['date_add']);
            $table->addIndex(['priority']);
            $table->addIndex(['value', 'executed', 'type']);
        }

        return $sql;
    }

    public function getTabs(): array
    {
        $defaultTabs = [];
        $defaultTabs[] = Tab::buildFromArray([
            'class_name' => 'PsLegalcomplianceConfigurationAdminParentController',
            'route_name' => 'ps_legalcompliance',
            'icon' => '',
            'wording' => 'Legalcompliance Module',
            'wording_domain' => 'Modules.Pslegalcompliance.Admin',
            'visible' => false,
            'parent_class_name' => 'AdminParentModulesSf',
            'name' => [
                'en' => 'Legalcompliance Module',
                'de' => 'Legalcompliance Modul',
            ],
        ]);

        $tabs = $this->tabs();

        if (!empty($tabs)) {
            $defaultTabs = array_merge($defaultTabs, $tabs);
        }

        $defaultTabs[] = Tab::buildFromArray([
            'class_name' => 'PsLegalcomplianceConfigurationAdminController',
            'route_name' => 'ps_legalcompliance_configuration',
            'icon' => '',
            'wording' => 'Configuration',
            'wording_domain' => 'Modules.Pslegalcompliance.Admin',
            'visible' => true,
            'parent_class_name' => 'PsLegalcomplianceConfigurationAdminParentController',
            'name' => [
                'en' => 'Configuration',
                'de' => 'Konfiguration',
            ],
        ]);

        if ($this->module->isPayment()) {
            $defaultTabs[] = Tab::buildFromArray([
                'class_name' => 'PsLegalcompliancePaymentAdminController',
                'route_name' => 'ps_legalcompliance_payment',
                'icon' => '',
                'wording' => 'Payment',
                'wording_domain' => 'Modules.Pslegalcompliance.Admin',
                'visible' => true,
                'parent_class_name' => 'PsLegalcomplianceConfigurationAdminParentController',
                'name' => [
                    'en' => 'Payment',
                    'de' => 'Status & Zahlung',
                ],
            ]);
        }

        if ($this->isUsingCron()) {
            $defaultTabs[] = Tab::buildFromArray([
                'class_name' => 'PsLegalcomplianceCronAdminController',
                'route_name' => 'ps_legalcompliance_cron',
                'icon' => '',
                'wording' => 'Cron',
                'wording_domain' => 'Modules.Pslegalcompliance.Admin',
                'visible' => true,
                'parent_class_name' => 'PsLegalcomplianceConfigurationAdminParentController',
                'name' => [
                    'en' => 'Cron',
                    'de' => 'Cron',
                ],
            ]);
        }

        $defaultTabs[] = Tab::buildFromArray([
            'class_name' => 'PsLegalcomplianceLogsAdminController',
            'route_name' => 'ps_legalcompliance_logs',
            'icon' => '',
            'wording' => 'Logs',
            'wording_domain' => 'Modules.Pslegalcompliance.Admin',
            'visible' => true,
            'parent_class_name' => 'PsLegalcomplianceConfigurationAdminParentController',
            'name' => [
                'en' => 'Logs',
                'de' => 'Logs',
            ],
        ]);

        $defaultTabs[] = Tab::buildFromArray([
            'class_name' => 'PsLegalcomplianceMaintenanceAdminController',
            'route_name' => 'ps_legalcompliance_maintenance',
            'icon' => '',
            'wording' => 'Maintenance',
            'wording_domain' => 'Modules.Pslegalcompliance.Admin',
            'visible' => true,
            'parent_class_name' => 'PsLegalcomplianceConfigurationAdminParentController',
            'name' => [
                'en' => 'Maintenance',
                'de' => 'Wartung',
            ],
        ]);

        $defaultTabs[] = Tab::buildFromArray([
            'class_name' => 'PsLegalcomplianceLicenseAdminController',
            'route_name' => 'ps_legalcompliance_license',
            'icon' => '',
            'wording' => 'License',
            'wording_domain' => 'Modules.Pslegalcompliance.Admin',
            'visible' => false,
            'parent_class_name' => 'PsLegalcomplianceConfigurationAdminParentController',
            'name' => [
                'en' => 'License',
                'de' => 'Lizenz',
            ],
        ]);

        return $defaultTabs;
    }

    public function getSqlInstall(): array
    {
        $schema = $this->getSql()->getSchema();

        return $schema->toSql(
            $this->connection->getDatabasePlatform()
        );
    }

    public function getSqlUninstall(): array
    {
        $schema = $this->getSql()->getSchema();

        return $schema->toDropSql(
            $this->connection->getDatabasePlatform()
        );
    }

    public function getTranslations()
    {
        return $this->translations();
    }

    public function getFixtures(): callable
    {
        return $this->fixtures();
    }

    public function getAll(): array
    {
        return [
            'config' => $this->getConfig(),
            'controllers' => $this->getControllers(),
            'cron' => $this->getCron(),
            'fixtures' => $this->getFixtures(),
            'hooks' => $this->getHooks(),
            'orderstates' => $this->getOrderStates(),
            'sql' => $this->getSql(),
            'tabs' => $this->getTabs(),
            'translations' => $this->getTranslations(),
        ];
    }

    private function convertToInstance($className, array $instances): array
    {
        return array_map(function ($instance) use ($className) {
            if (!($instance instanceof $className)) {
                return new $className($instance);
            }

            return $instance;
        }, $instances);
    }
}
