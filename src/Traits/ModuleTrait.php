<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Traits;

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Log\Logger;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Log\LogLevel;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Log\LogRepository;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Mail\MailPartialTemplateRenderer;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Module\AbstractSettings;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Module\ConfigurationAdapter;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Module\Install;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings;
use PrestaShop\PrestaShop\Adapter\Configuration as ConfigurationAdapterPrestaShop;
use PrestaShop\PrestaShop\Adapter\ContainerBuilder;
use PrestaShop\PrestaShop\Adapter\ContainerFinder;
use PrestaShop\PrestaShop\Core\Exception\ContainerNotFoundException;
use PrestaShop\PrestaShop\Core\MailTemplate\Layout\Layout;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeCollectionInterface;
use PrestaShop\PrestaShop\Core\MailTemplate\ThemeInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

trait ModuleTrait
{
    const GC_VERSION = '9.0.2';
    const GC_SUBVERSION = '46';

    public $config;
    public $displayNamePre = '';
    public $displayNamePost = '';
    public $extra_mail_vars = [];
    public $installOrderStates = true;
    public $isPaidOnOrderCreation = true;
    public $logger;
    public $multishop_context = \Shop::CONTEXT_ALL | \Shop::CONTEXT_GROUP | \Shop::CONTEXT_SHOP;
    public $secureKey = '';

    protected $mailPartialRenderer;
    protected $settings;
    protected $templatesForEmailVars = [];

    public function initModule()
    {
        $this->need_instance = 0;

        $settings = $this->getSettings();

        $tabs = $settings->getTabs();
        $this->tabs = array_map(function ($tab) {
            return $tab->toArray();
        }, $tabs);

        $controllers = $settings->getControllers();
        $this->controllers = array_map(function ($controller) {
            return (string) $controller;
        }, $controllers);

        $this->secureKey = \Tools::hash($this->name);

        if ($this->isPayment()) {
            $this->tab = 'payments_gateways';
            $this->need_instance = 1;
        }

        $this->templatesForEmailVars[] = $this->name;

        if (method_exists($this, 'parentInitModule')) {
            $this->parentInitModule();
        }
    }

    public function getSettings(): AbstractSettings
    {
        if ($this->settings === null) {
            try {
                $containerFinder = new ContainerFinder(\Context::getContext());
                $container = $containerFinder->getContainer();
            } catch (ContainerNotFoundException $e) {
                $container = ContainerBuilder::getContainer('front', _PS_MODE_DEV_);
            }

            $connection = $container->get('doctrine.dbal.default_connection');

            $this->settings = new Settings(
                $this,
                $connection,
                _DB_PREFIX_
            );
        }

        return $this->settings;
    }

    public function install(): bool
    {
        if (!parent::install()) {
            return false;
        }

        $install = new Install(
            $this,
            $this->getSettings()
        );
        $result = $install->installModule();

        if ($result && method_exists($this, 'installPost')) {
            $result = $this->installPost();
        }

        return $result;
    }

    public function uninstall(): bool
    {
        $install = new Install(
            $this,
            $this->getSettings()
        );
        $result = $install->uninstallModule();

        if ($result && method_exists($this, 'uninstallPost')) {
            $result = $this->uninstallPost();
        }

        return parent::uninstall() && $result;
    }

    public function reset(): bool
    {
        $install = new Install(
            $this,
            $this->getSettings()
        );
        $result = $install->resetModule();

        if ($result && method_exists($this, 'resetPost')) {
            $result = $this->resetPost();
        }

        return $result;
    }

    public function isActive(): bool
    {
        $active = true;

        $active &= $this->active;

        if (method_exists($this, 'parentIsActive')) {
            $active &= $this->parentIsActive($active);
        }

        return (bool) $active;
    }

    public function isPayment(): bool
    {
        static $_isPaymentModule = null;

        if ($_isPaymentModule === null) {
            $_isPaymentModule =
                ($this instanceof \PaymentModule)
                && is_file($this->getLocalPath() . 'controllers/front/payment.php');
        }

        return $_isPaymentModule;
    }

    public function hasCronjobs(): bool
    {
        static $_hasCronjobs = null;

        if ($_hasCronjobs === null) {
            $settings = $this->getSettings();

            $_hasCronjobs =
                !empty($settings->getCron())
                && is_file($this->getLocalPath() . 'controllers/front/cron.php');
        }

        return $_hasCronjobs;
    }

    public function hookActionAdminControllerSetMedia(array $params)
    {
        $route = '';

        if (!empty($params['request'])) {
            $route = $params['request']->attributes->get('_route');
        }

        if (strpos($route, $this->name) !== false) {
            $context = \Context::getContext();

            $context->controller->addCss($this->getPathUri() . 'views/css/admin/module.css');
            $context->controller->addJs($this->getPathUri() . 'views/js/admin/module.js');

            \Media::addJsDef([
                'txtUpdateLicenseCode' => $this->trans('Do you really want to update the license code for this module?', [], 'Modules.Pslegalcompliance.Admin'),
            ]);
        }

        if (method_exists($this, 'parentHookActionAdminControllerSetMedia')) {
            $this->parentHookActionAdminControllerSetMedia($params);
        }
    }

    public function hookActionFrontControllerSetMedia(array $params)
    {
        if (!$this->isActive()) {
            return;
        }

        $context = \Context::getContext();

        $context->controller->registerStylesheet(
            $this->name,
            '/modules/' . $this->name . '/views/css/front/module.css',
            [
                'media' => 'all',
                'priority' => 2000,
            ]
        );

        $context->controller->registerJavascript(
            $this->name,
            '/modules/' . $this->name . '/views/js/front/module.js',
            [
                'position' => 'bottom',
                'priority' => 2000,
                'attributes' => 'defer',
            ]
        );

        if (method_exists($this, 'parentHookActionFrontControllerSetMedia')) {
            $this->parentHookActionFrontControllerSetMedia($params);
        }
    }

    public function hookActionEmailSendBefore($params)
    {
        if (!in_array($params['template'], $this->templatesForEmailVars)) {
            return;
        }

        $iso = \Language::getIsoById((int) $params['idLang']);

        if (empty($iso)) {
            return;
        }

        $templatePath = _PS_MODULE_DIR_ . $this->name . '/mails/' . $iso . '/' . $params['template'] . '.html';

        if (is_file($templatePath)) {
            $params['templatePath'] = _PS_MODULE_DIR_ . $this->name . '/mails/';
        }

        if (method_exists($this, 'parentHookActionEmailSendBefore')) {
            $this->parentHookActionEmailSendBefore($params);
        }
    }

    public function hookActionGetExtraMailTemplateVars($params)
    {
        if (!in_array($params['template'], $this->templatesForEmailVars)) {
            return [];
        }

        $idOrder = (int) ($params['template_vars']['{id_order}'] ?? 0);

        $order = new \Order($idOrder);

        if (\Validate::isLoadedObject($order)) {
            $params['extra_template_vars'] = $this->getOrderExtraMailVars($order);
        }

        if (method_exists($this, 'parentHookActionGetExtraMailTemplateVars')) {
            $params['extra_template_vars'] = $this->parentHookActionGetExtraMailTemplateVars($params, $params['template_vars'], $params['extra_template_vars']);
        }
    }

    public function hookActionListMailThemes(array $params)
    {
        if (!isset($params['mailThemes'])) {
            return;
        }

        /** @var ThemeCollectionInterface $themes */
        $themes = $params['mailThemes'];

        $allThemesDir = $this->name . '/mails/themes';

        $txtThemesDir = $allThemesDir . '/txt';
        $txtThemesLocalDir = _PS_MODULE_DIR_ . $txtThemesDir;

        /** @var ThemeInterface $theme */
        foreach ($themes as $theme) {
            $themeName = $theme->getName();

            $themesDir = $allThemesDir . '/' . $themeName;
            $themesLocalDir = _PS_MODULE_DIR_ . $themesDir;

            if (!is_dir($themesLocalDir)) {
                $themesDir = $allThemesDir . '/modern';
                $themesLocalDir = _PS_MODULE_DIR_ . $themesDir;
            }

            $finder = new Finder();
            $finder->in($themesLocalDir)->files()->name('/\.html\.twig$/i');

            foreach ($finder as $file) {
                $fileTitle = substr($file->getFilename(), 0, -10);

                $filePathTxt = '';

                if (is_file($txtThemesLocalDir . '/' . $fileTitle . '.txt')) {
                    $filePathTxt = '@Modules/' . $txtThemesDir . '/' . $fileTitle . '.txt';
                }

                $theme->getLayouts()->add(new Layout(
                    $fileTitle,
                    '@Modules/' . $themesDir . '/' . $file->getFilename(),
                    $filePathTxt,
                    $this->name
                ));
            }
        }
    }

    public function getOrderExtraMailVars(\Order $order)
    {
        $data = [];

        $shop = new \Shop((int) $order->id_shop);
        $language = new \Language((int) $order->id_lang);
        $customer = new \Customer((int) $order->id_customer);
        $currency = new \Currency((int) $order->id_currency, null, (int) $shop->id);
        $invoice = new \Address((int) $order->id_address_invoice);
        $delivery = new \Address((int) $order->id_address_delivery);
        $carrier = new \Carrier((int) $order->id_carrier);

        $context = \Context::getContext();

        $currentLanguage = $context->language;
        $context->language = $language;
        $context->getTranslator()->setLocale($language->locale);

        $deliveryState = $delivery->id_state ? new \State((int) $delivery->id_state) : null;
        $invoiceState = $invoice->id_state ? new \State((int) $invoice->id_state) : null;

        $productVarTplList = [];

        $currentLocale = \Tools::getContextLocale($context);

        foreach ($order->getProducts() as $product) {
            $unitPriceTaxInclFormatted = $currentLocale->formatPrice(
                $order->total_paid,
                $currency->iso_code
            );

            $totalProiceTaxInclFormatted = $currentLocale->formatPrice(
                $order->total_paid,
                $currency->iso_code
            );

            $productVarTpl = [
                'id_product' => $product['product_id'],
                'reference' => $product['product_reference'],
                'name' => $product['product_name'],
                'unit_price' => $unitPriceTaxInclFormatted,
                'price' => $totalProiceTaxInclFormatted,
                'quantity' => $product['product_quantity'],
                'customization' => [],
            ];

            $customizedDatas = \Product::getAllCustomizedDatas((int) $order->id_cart, (int) $order->id_lang, true, (int) $order->id_shop);

            if (isset($customizedDatas[$product['product_id']][$product['product_attribute_id']])) {
                $productVarTpl['customization'] = [];

                foreach ($customizedDatas[$product['product_id']][$product['product_attribute_id']][$order->id_address_delivery] as $customization) {
                    $customizationText = '';

                    if (isset($customization['datas'][\Product::CUSTOMIZE_TEXTFIELD])) {
                        foreach ($customization['datas'][\Product::CUSTOMIZE_TEXTFIELD] as $text) {
                            $customizationText .= '<strong>' . $text['name'] . '</strong>: ' . $text['value'] . '<br />';
                        }
                    }

                    if (isset($customization['datas'][\Product::CUSTOMIZE_FILE])) {
                        $customizationText .= $this->trans('%count% image(s)', ['%count%' => count($customization['datas'][\Product::CUSTOMIZE_FILE])], 'Modules.Pslegalcompliance.Admin') . '<br />';
                    }

                    $customizationCuantity = (int) $product['product_quantity'];

                    $customizationPriceFormatted = $currentLocale->formatPrice(
                        $customizationCuantity * $product['unit_price_tax_incl'],
                        $currency->iso_code
                    );

                    $productVarTpl['customization'][] = [
                        'customization_text' => $customizationText,
                        'customization_quantity' => $customizationCuantity,
                        'quantity' => $customizationPriceFormatted,
                    ];
                }
            }

            $productVarTplList[] = $productVarTpl;
        }

        $productListTxt = '';
        $productListHtml = '';

        if (count($productVarTplList) > 0) {
            $productListTxt = $this->renderPartialEmailTemplate('order_conf_product_list.txt', \Mail::TYPE_TEXT, ['list' => $productVarTplList]);
            $productListHtml = $this->renderPartialEmailTemplate('order_conf_product_list.tpl', \Mail::TYPE_HTML, ['list' => $productVarTplList]);
        }

        $cartRulesList = [];

        foreach ($order->getCartRules() as $cartRule) {
            $cartValuesFormatted = $currentLocale->formatPrice(
                $cartRule['value'],
                $currency->iso_code
            );

            $cartRulesList[] = [
                'voucher_name' => $cartRule['name'],
                'voucher_reduction' => ($cartRule['value'] != 0.00 ? '-' : '') . $cartValuesFormatted,
            ];
        }

        $cartRulesListTxt = '';
        $cartRulesListHtml = '';

        if (count($cartRulesList) > 0) {
            $cartRulesListTxt = $this->renderPartialEmailTemplate('order_conf_cart_rules.txt', \Mail::TYPE_TEXT, ['list' => $cartRulesList]);
            $cartRulesListHtml = $this->renderPartialEmailTemplate('order_conf_cart_rules.tpl', \Mail::TYPE_HTML, ['list' => $cartRulesList]);
        }

        $data = [
            '{firstname}' => $customer->firstname,
            '{lastname}' => $customer->lastname,
            '{email}' => $customer->email,
            '{delivery_block_txt}' => \AddressFormat::generateAddress($delivery, ['avoid' => []], \AddressFormat::FORMAT_NEW_LINE, ' '),
            '{invoice_block_txt}' => \AddressFormat::generateAddress($invoice, ['avoid' => []], \AddressFormat::FORMAT_NEW_LINE, ' '),
            '{delivery_block_html}' => \AddressFormat::generateAddress($delivery, ['avoid' => []], '<br />', ' ', [
                'firstname' => '<span style="font-weight:bold;">%s</span>',
                'lastname' => '<span style="font-weight:bold;">%s</span>',
            ]),
            '{invoice_block_html}' => \AddressFormat::generateAddress($invoice, ['avoid' => []], '<br />', ' ', [
                'firstname' => '<span style="font-weight:bold;">%s</span>',
                'lastname' => '<span style="font-weight:bold;">%s</span>',
            ]),
            '{delivery_company}' => $delivery->company,
            '{delivery_firstname}' => $delivery->firstname,
            '{delivery_lastname}' => $delivery->lastname,
            '{delivery_address1}' => $delivery->address1,
            '{delivery_address2}' => $delivery->address2,
            '{delivery_city}' => $delivery->city,
            '{delivery_postal_code}' => $delivery->postcode,
            '{delivery_country}' => $delivery->country,
            '{delivery_state}' => $delivery->id_state ? $deliveryState->name : '',
            '{delivery_phone}' => ($delivery->phone) ? $delivery->phone : $delivery->phone_mobile,
            '{delivery_other}' => $delivery->other,
            '{invoice_company}' => $invoice->company,
            '{invoice_vat_number}' => $invoice->vat_number,
            '{invoice_firstname}' => $invoice->firstname,
            '{invoice_lastname}' => $invoice->lastname,
            '{invoice_address2}' => $invoice->address2,
            '{invoice_address1}' => $invoice->address1,
            '{invoice_city}' => $invoice->city,
            '{invoice_postal_code}' => $invoice->postcode,
            '{invoice_country}' => $invoice->country,
            '{invoice_state}' => $invoice->id_state ? $invoiceState->name : '',
            '{invoice_phone}' => ($invoice->phone) ? $invoice->phone : $invoice->phone_mobile,
            '{invoice_other}' => $invoice->other,
            '{order_name}' => $order->getUniqReference(),
            '{id_order}' => $order->id,
            '{date}' => \Tools::displayDate($order->date_add, true),
            '{now}' => \Tools::displayDate(date('Y-m-d')),
            '{carrier}' => ($order->isVirtual() || $carrier->name == '') ? $this->trans('No carrier', [], 'Modules.Pslegalcompliance.Admin') : $carrier->name,
            '{payment}' => \Tools::substr($order->payment, 0, 255) . ($order->hasBeenPaid() ? '' : '&nbsp;' . $this->trans('(waiting for validation)', [], 'Modules.Pslegalcompliance.Admin')),
            '{products}' => $productListHtml,
            '{products_txt}' => $productListTxt,
            '{discounts}' => $cartRulesListHtml,
            '{discounts_txt}' => $cartRulesListTxt,
            '{total_paid}' => $currentLocale->formatPrice($order->total_paid, $currency->iso_code),
            '{total_shipping_tax_excl}' => $currentLocale->formatPrice($order->total_shipping_tax_excl, $currency->iso_code),
            '{total_shipping_tax_incl}' => $currentLocale->formatPrice($order->total_shipping_tax_incl, $currency->iso_code),
            '{total_tax_paid}' => $currentLocale->formatPrice($order->total_paid_tax_incl - $order->total_paid_tax_excl, $currency->iso_code),
            '{recycled_packaging_label}' => $order->recyclable ? $this->trans('Yes', [], 'Modules.Pslegalcompliance.Admin') : $this->trans('No', [], 'Modules.Pslegalcompliance.Admin'),
        ];

        if (\Product::getTaxCalculationMethod() == PS_TAX_EXC) {
            $data = array_merge($data, [
                '{total_products}' => $currentLocale->formatPrice($order->total_products, $currency->iso_code),
                '{total_discounts}' => $currentLocale->formatPrice($order->total_discounts_tax_excl, $currency->iso_code),
                '{total_shipping}' => $currentLocale->formatPrice($order->total_shipping_tax_excl, $currency->iso_code),
                '{total_wrapping}' => $currentLocale->formatPrice($order->total_wrapping_tax_excl, $currency->iso_code),
            ]);
        } else {
            $data = array_merge($data, [
                '{total_products}' => $currentLocale->formatPrice($order->total_products_wt, $currency->iso_code),
                '{total_discounts}' => $currentLocale->formatPrice($order->total_discounts, $currency->iso_code),
                '{total_shipping}' => $currentLocale->formatPrice($order->total_shipping, $currency->iso_code),
                '{total_wrapping}' => $currentLocale->formatPrice($order->total_wrapping, $currency->iso_code),
            ]);
        }

        $context->language = $currentLanguage;
        $context->getTranslator()->setLocale($currentLanguage->locale);

        if (method_exists($this, 'parentGetOrderExtraMailVars')) {
            $data = $this->parentGetOrderExtraMailVars($data, $order);
        }

        return $data;
    }

    public function renderPartialEmailTemplate($template_name, $mail_type, $var): string
    {
        $email_configuration = \Configuration::get('PS_MAIL_TYPE');
        if ($email_configuration != $mail_type && $email_configuration != \Mail::TYPE_BOTH) {
            return '';
        }

        $context = \Context::getContext();

        return $this->getMailPartialTemplateRenderer()->render($template_name, $context->language, $var);
    }

    protected function getMailPartialTemplateRenderer(): MailPartialTemplateRenderer
    {
        if (!$this->mailPartialRenderer) {
            $context = \Context::getContext();

            $this->mailPartialRenderer = new MailPartialTemplateRenderer($this->name, $context->smarty);
        }

        return $this->mailPartialRenderer;
    }

    public function getConfig(): ConfigurationAdapter
    {
        if (empty($this->config)) {
            try {
                $this->config = $this->get('onlineshopmodule.module.legalcompliance.configurationadapter');
            } catch (\Throwable $e) {
                // Do nothing!
            }

            if (empty($this->config)) {
                $this->config = new ConfigurationAdapter(
                    $this,
                    new ConfigurationAdapterPrestaShop(),
                    null
                );
            }
        }

        return $this->config;
    }

    public function getLogger(): Logger
    {
        if (empty($this->logger)) {
            try {
                $this->logger = $this->get('onlineshopmodule.module.legalcompliance.logger');
            } catch (\Throwable $e) {
                // Do nothing!
            }

            if (empty($this->logger)) {
                $this->logger = new Logger(
                    new LogRepository(
                        new Filesystem(),
                        $this->name . '/'
                    ),
                    new LogLevel(
                        $this->getConfig()
                    )
                );
            }
        }

        return $this->logger;
    }
}
