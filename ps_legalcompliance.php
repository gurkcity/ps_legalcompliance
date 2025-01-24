<?php
/**
 * 2007-2017 PrestaShop.
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author     PrestaShop SA <contact@prestashop.com>
 * @copyright  2007-2017 PrestaShop SA
 * @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
 */

use PrestaShop\PrestaShop\Core\Foundation\Database\EntityManager;
use PrestaShop\PrestaShop\Core\Foundation\Filesystem\FileSystem;
use PrestaShop\PrestaShop\Core\Email\EmailLister;
use PrestaShop\PrestaShop\Core\Checkout\TermsAndConditions;
use PSLegalcompliance\EmailTemplateFinder;
use PSLegalcompliance\Install;
use PSLegalcompliance\Roles;
use PSLegalcompliance\VirtualCart;

require_once __DIR__ . '/vendor/autoload.php';

if (!defined('_PS_VERSION_')) {
    exit;
}

class Ps_LegalCompliance extends Module
{
    protected $config_form = false;
    protected $entity_manager;
    protected $filesystem;
    protected $email;
    protected $_errors = [];
    protected $_warnings = [];

    public function __construct(
        EntityManager $entity_manager,
        FileSystem $fs,
        EmailLister $email
    ) {
        $this->name = 'ps_legalcompliance';
        $this->tab = 'administration';
        $this->version = '8.4.4';
        $this->author = 'PrestaShop';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        /* Register dependencies to module */
        $this->entity_manager = $entity_manager;
        $this->filesystem = $fs;
        $this->email = $email;

        $this->displayName = $this->trans('Legal Compliance', [], 'Modules.Legalcompliance.Admin');
        $this->description = $this->trans('Keep on growing your business serenely, sell all over Europe while complying with the applicable e-commerce laws.', [], 'Modules.Legalcompliance.Admin');
        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall this module?', [], 'Modules.Legalcompliance.Admin');

        $this->ps_versions_compliancy = [
            'min' => '8.0',
            'max' => _PS_VERSION_
        ];

        $this->_errors = [];
    }

    public function install()
    {
        if (!parent::install()) {
            return false;
        }

        $install = new Install($this, $this->entity_manager, $this->email, $this->context);

        return $install->install();
    }

    public function uninstall()
    {
        $install = new Install($this, $this->entity_manager, $this->email, $this->context);

        if ($install->uninstall()) {
            return parent::uninstall();
        }

        return false;
    }

    public function disable($force_all = false)
    {
        if (!parent::disable()) {
            return false;
        }

        $install = new Install($this, $this->entity_manager, $this->email, $this->context);

        return $install->disable();
    }

    public function hookDisplayCartTotalPriceLabel($param)
    {
        $smartyVars = [];

        if (Configuration::get('AEUC_LABEL_TAX_INC_EXC')) {
            $customer_default_group_id = (int) $this->context->customer->id_default_group;
            $customer_default_group = new Group($customer_default_group_id);

            if (
                Configuration::get('PS_TAX')
                && $this->context->country->display_tax_label
                && !(
                    Validate::isLoadedObject($customer_default_group)
                    && $customer_default_group->price_display_method
                )
            ) {
                $smartyVars['price']['tax_str_i18n'] = $this->trans('Tax included', [], 'Shop.Theme.Checkout');
            } else {
                $smartyVars['price']['tax_str_i18n'] = $this->trans('Tax excluded', [], 'Shop.Theme.Checkout');
            }
        }

        if (isset($param['from'])) {
            if ($param['from'] == 'shopping_cart') {
                $smartyVars['css_class'] = 'aeuc_tax_label_shopping_cart';
            }
            if ($param['from'] == 'blockcart') {
                $smartyVars['css_class'] = 'aeuc_tax_label_blockcart';
            }
        }

        $this->smarty->assign([
            'smartyVars' => $smartyVars,
        ]);

        return $this->fetch('module:' . $this->name . '/views/templates/hook/displayCartTotalPriceLabel.tpl');
    }

    public function hookDisplayOverrideTemplate($param)
    {
        if (
            $this->context->controller instanceof OrderController
            && !$this->context->controller->ajax
        ) {
            return $this->getTemplatePath('hookDisplayOverrideTemplateFooter.tpl');
        }
    }

    public function hookDisplayCheckoutSummaryTop($param)
    {
        $template = 'module:' . $this->name . '/views/templates/hook/hookDisplayCheckoutSummaryTop.tpl';
        $cacheId = $this->getCacheId($this->name . '|hookDisplayCheckoutSummaryTop');

        if (!$this->isCached($template, $cacheId)) {
            $this->smarty->assign([
                'link_shopping_cart' => $this->context->link->getPageLink(
                    'cart',
                    null,
                    $this->context->language->id,
                    ['action' => 'show']
                ),
            ]);
        }

        return $this->fetch($template, $cacheId);
    }

    public function hookDisplayReassurance($param)
    {
        if (
            !($this->context->controller instanceof OrderController)
            || !($this->context->controller instanceof CartController)
        ) {
            return;
        }

        $template = 'module:' . $this->name . '/views/templates/hook/hookDisplayReassurance.tpl';
        $cacheId = $this->getCacheId($this->name . '|hookDisplayReassurance');

        if (!$this->isCached($template, $cacheId)) {
            $custom_cart_text = Configuration::get('AEUC_LABEL_CUSTOM_CART_TEXT', $this->context->language->id);

            $this->smarty->assign([
                'custom_cart_text' => trim($custom_cart_text),
            ]);
        }

        return $this->fetch($template, $cacheId);
    }

    public function hookDisplayFooter($param)
    {
        if (!Configuration::get('AEUC_LINKBLOCK_FOOTER')) {
            return;
        }

        $template = 'module:' . $this->name . '/views/templates/hook/hookDisplayFooter.tpl';
        $cacheId = $this->getCacheId($this->name . '|hookDisplayFooter');

        if (!$this->isCached($template, $cacheId)) {
            $cms_roles_to_be_displayed = [
                Roles::NOTICE,
                Roles::CONDITIONS,
                Roles::REVOCATION,
                Roles::PRIVACY,
                Roles::SHIP_PAY,
                Roles::ENVIRONMENTAL,
            ];

            $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
            $cms_pages_associated = $cms_role_repository->findByName($cms_roles_to_be_displayed);
            $is_ssl_enabled = (bool) Configuration::get('PS_SSL_ENABLED');
            $cms_links = [];

            foreach ($cms_pages_associated as $cms_page_associated) {
                if (
                    ($cms_page_associated instanceof CMSRole)
                    && $cms_page_associated->id_cms > 0
                ) {
                    $cms = new CMS((int) $cms_page_associated->id_cms);

                    if (!Validate::isLoadedObject($cms)) {
                        // skip non loaded object
                        continue;
                    }

                    $cms_links[] = [
                        'link' => $this->context->link->getCMSLink($cms->id, null, $is_ssl_enabled),
                        'id' => 'cms-page-' . $cms->id,
                        'title' => $cms->meta_title[$this->context->language->id],
                        'desc' => $cms->meta_description[$this->context->language->id],
                    ];
                }
            }

            $this->smarty->assign([
                'cms_links' => $cms_links,
            ]);
        }

        return $this->fetch($template, $cacheId);
    }

    public function hookDisplayFooterAfter($param)
    {
        if (
            !isset($this->context->controller->php_self)
            || !in_array($this->context->controller->php_self, ['index', 'category', 'prices-drop', 'new-products', 'best-sales', 'search', 'product'])
        ) {
            return;
        }

        $idCountry = !empty($this->context->country->id) ? $this->context->country->id : 0;

        $template = 'module:' . $this->name . '/views/templates/hook/hookDisplayFooterAfter.tpl';
        $cacheId = $this->getCacheId($this->name . '|hookDisplayFooterAfter|' . $this->context->controller->php_self . '|' . $idCountry);

        if (!$this->isCached($template, $cacheId)) {
            $cmsRepository = $this->entity_manager->getRepository('CMS');
            $cmsRoleRepository = $this->entity_manager->getRepository('CMSRole');
            $cmsPageShippingPay = $cmsRoleRepository->findOneByName(Roles::SHIP_PAY);

            $link_shipping = false;

            if ($cmsPageShippingPay->id_cms > 0) {
                $cms_shipping_pay = $cmsRepository->i10nFindOneById(
                    (int) $cmsPageShippingPay->id_cms,
                    (int) $this->context->language->id,
                    (int) $this->context->shop->id
                );

                if ($cms_shipping_pay) {
                    $link_shipping = $this->context->link->getCMSLink(
                        $cms_shipping_pay,
                        $cms_shipping_pay->link_rewrite,
                        (bool) Configuration::get('PS_SSL_ENABLED')
                    );
                }
            }

            if (
                $this->context->controller->php_self == 'product'
                && (int) Configuration::get('AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL') == 1
            ) {
                $delivery_addtional_info = Configuration::get('AEUC_LABEL_DELIVERY_ADDITIONAL', (int) $this->context->language->id);

                $this->smarty->assign('delivery_additional_information', $delivery_addtional_info);
            }

            $customer_default_group = new Group((int) $this->context->customer->id_default_group);

            if (
                Configuration::get('PS_TAX')
                && $this->context->country->display_tax_label
                && !(
                    Validate::isLoadedObject($customer_default_group)
                    && $customer_default_group->price_display_method
                )
            ) {
                $tax_included = true;
            } else {
                $tax_included = false;
            }

            $this->smarty->assign([
                'show_shipping' => (bool) Configuration::get('AEUC_LABEL_SHIPPING_INC_EXC'),
                'link_shipping' => $link_shipping,
                'tax_included' => $tax_included,
                'display_tax_information' => Configuration::get('AEUC_LABEL_TAX_FOOTER'),
            ]);
        }

        return $this->fetch($template, $cacheId);
    }

    private function getCmsRolesForMailtemplate($tpl_name, $id_lang)
    {
        $tpl_name_exploded = explode('.', $tpl_name);

        if (is_array($tpl_name_exploded)) {
            $tpl_name = (string) $tpl_name_exploded[0];
        }

        $mail_id = AeucEmailEntity::getMailIdFromTplFilename($tpl_name);

        if (!isset($mail_id['id_mail'])) {
            return [];
        }

        $cms_role_ids = AeucCMSRoleEmailEntity::getCMSRoleIdsFromIdMail((int) $mail_id['id_mail']);

        $tmp_cms_role_list = array_column($cms_role_ids, 'id_cms_role');

        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');

        return $tmp_cms_role_list
            ? $cms_role_repository->findByIdCmsRole($tmp_cms_role_list)
            : [];
    }

    public function hookActionEmailSendBefore($params)
    {
        if (!isset($params['template'])) {
            return;
        }

        $cms_roles = $this->getCmsRolesForMailTemplate((string) $params['template'], (int) $params['idLang']);
        $cms_repo = $this->entity_manager->getRepository('CMS');
        $pdf_attachment = $this->getPDFAttachmentOptions();

        if (empty($cms_roles)) {
            return;
        }

        foreach ($cms_roles as $cms_role) {
            if (!in_array($cms_role->id, $pdf_attachment)) {
                continue;
            }

            $cms_page = $cms_repo->i10nFindOneById(
                (int) $cms_role->id_cms,
                (int) $params['idLang'],
                $this->context->shop->id
            );

            if (!isset($cms_page->content)) {
                continue;
            }

            $pdf = new PDF($cms_page, 'CMSContent', $this->context->smarty);

            $params['fileAttachment']['cms_' . $cms_page->id] = [
                'content' => $pdf->render('S'),
                'name' => $cms_page->meta_title . '.pdf',
                'mime' => 'application/pdf'
            ];
        }
    }

    public function hookActionEmailAddAfterContent($param)
    {
        if (
            !isset($param['template'])
            || !isset($param['template_html'])
            || !isset($param['template_txt'])
        ) {
            return;
        }

        $id_lang = (int) $param['id_lang'];
        $cms_roles = $this->getCmsRolesForMailTemplate((string) $param['template'], (int) $param['id_lang']);

        $cms_repo = $this->entity_manager->getRepository('CMS');
        $cms_contents = [];
        $pdf_attachment = $this->getPDFAttachmentOptions();

        foreach ($cms_roles as $cms_role) {
            // exclude the CMS content from the mail if the PDF Attachment is enabled for the cms role
            if (in_array($cms_role->id, $pdf_attachment)) {
                continue;
            }

            $cms_page = $cms_repo->i10nFindOneById((int) $cms_role->id_cms, $id_lang, $this->context->shop->id);

            if (!isset($cms_page->content)) {
                continue;
            }

            $cms_contents[] = $cms_page->content;
            $param['template_txt'] .= strip_tags($cms_page->content, true);
        }

        $this->context->smarty->assign([
            'cms_contents' => $cms_contents,
            'legal_mail_footer' => Configuration::get('LEGAL_MAIL_FOOTER', $id_lang),
        ]);

        $str = $param['template_html'];

        try {
            $var_matches = preg_match_all('~\{(.+?)\}~', $str, $matches);

            if ($var_matches && !empty($matches[0])) {
                foreach ($matches[0] as $i => $varname) {
                    $str = str_replace($varname, "__var_{$i}__", $str);
                }
            }

            $doc = new DOMDocument();
            $doc->loadHTML($str);
            $footer_doc = new DOMDocument();

            if (Configuration::get('PS_MAIL_THEME') == 'classic') {
                $wrapper = $doc->getElementsByTagName('table')->item(0); //selects tbody

                // escape "&" to "&amp;"
                $hook_email_wrapper = preg_replace('/&(?!amp)/', '&amp;', $this->display(__FILE__, 'hook-email-wrapper_classic.tpl'));

                $footer_doc->loadHTML('<!DOCTYPE html>
                    <html lang="' . (new Language($id_lang))->iso_code .'">
                        <head><meta charset="utf-8"></head><body>' . $hook_email_wrapper . '</body></html>');

                $wrapper->appendChild($footer_doc);
            } else {
                $divs = $doc->getElementsByTagName('div'); //selects first wrapping div
                $k = 0;

                foreach ($divs as $div) {
                    $div_class_attribute = $divs->item($k)->getAttribute('class');

                    if ($div_class_attribute == 'shadow wrapper-container') {
                        $wrapper = $divs->item($k);
                    }

                    $k++;
                }

                // escape "&" to "&amp;"
                $hook_email_wrapper = preg_replace('/&(?!amp)/', '&amp;', $this->display(__FILE__, 'hook-email-wrapper.tpl'));

                $footer_doc->loadHTML('<!DOCTYPE html>
                    <html lang="' . (new Language($id_lang))->iso_code .'">
                        <head><meta charset="utf-8"></head><body>' . $hook_email_wrapper . '</body></html>');

                for ($index = 0; $index < $footer_doc->getElementsByTagName('div')->length; $index++) {
                    $clone_node = $doc->importNode(
                        $footer_doc->getElementsByTagName('div')->item($index)->cloneNode(true),
                        true
                    );

                    $tr = $doc->createElement("tr");
                    $td = $doc->createElement("td");
                    $tr->appendChild($td);

                    if (isset($wrapper)) {
                        $wrapper->appendChild($tr);
                    }

                    $td->appendChild($clone_node);
                }
            }

            $html = $doc->saveHTML();

            if ($var_matches && !empty($matches[0])) {
                foreach ($matches[0] as $i => $varname) {
                    $html = str_replace("__var_{$i}__", $varname, $html);
                }
            }

            $param['template_html'] = $html;
        } catch (Throwable $e) {
            $param['template_html'] .= $this->display(__FILE__, 'hook-email-wrapper.tpl');
        }
    }

    public function hookSendMailAlterTemplateVars($param)
    {
        if (!isset($param['template']) && !isset($param['{carrier}'])) {
            return;
        }

        $tpl_name = (string) $param['template'];
        $tpl_name_exploded = explode('.', $tpl_name);

        if (is_array($tpl_name_exploded)) {
            $tpl_name = (string) $tpl_name_exploded[0];
        }

        if ('order_conf' !== $tpl_name) {
            return;
        }

        $carrier = new Carrier((int) $param['cart']->id_carrier);

        if (!Validate::isLoadedObject($carrier)) {
            return;
        }

        $delay = $carrier->delay[(int) $param['cart']->id_lang] ?? '';

        if ($delay == '') {
            return;
        }

        $param['template_vars']['{carrier}'] .= ' - ' . $delay;
    }

    public function hookDisplayHeader($param)
    {
        $this->context->controller->registerStylesheet(
            'modules-aeuc_front',
            'modules/' . $this->name . '/views/css/aeuc_front.css',
            [
                'media' => 'all',
                'priority' => 150
            ]
        );

        if (
            $this->context->controller instanceof CMSController
            && $this->isPrintableCMSPage()
        ) {
            $this->context->controller->registerStylesheet(
                'modules-aeuc_print',
                'modules/' . $this->name . '/views/css/aeuc_print.css',
                [
                    'media' => 'print',
                    'priority' => 150
                ]
            );
        }

        if (Tools::getValue('direct_print') == '1') {
            $this->context->controller->registerJavascript(
                'modules-fo_aeuc_print',
                'modules/'.$this->name.'/views/js/fo_aeuc_print.js',
                [
                    'position' => 'bottom',
                    'priority' => 150
                ]
            );
        }

        $this->context->controller->registerJavascript(
            'modules-fo_aeuc_tnc',
            'modules/'.$this->name.'/views/js/fo_aeuc_tnc.js',
            [
                'position' => 'bottom',
                'priority' => 150,
                'attributes' => 'defer'
            ]
        );

        if ($this->context->controller instanceof OrderController) {
            $this->context->controller->registerJavascript(
                'modules-' . $this->name . '-checkout',
                'modules/' . $this->name . '/views/js/checkout.js',
                [
                    'position' => 'bottom',
                    'priority' => 150,
                ]
            );

            Media::addJsDef([
                'cartEditLinkTitle' => $this->trans('edit', [], 'Modules.Legalcompliance.Shop'),
                'cartEditLinkUrl' => $this->context->link->getPageLink('cart', null, null, ['action' => 'show']),
            ]);
        }
    }

    protected function isPrintableCMSPage(int $idCms): bool
    {
        $printable_cms_pages = [];
        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');

        foreach ([Roles::CONDITIONS, Roles::REVOCATION, Roles::SHIP_PAY, Roles::PRIVACY] as $cms_page_name) {
            $cms_page_associated = $cms_role_repository->findOneByName($cms_page_name);

            if (
                ($cms_page_associated instanceof CMSRole)
                && $cms_page_associated->id_cms
            ) {
                $printable_cms_pages[] = (int) $cms_page_associated->id_cms;
            }
        }

        return in_array($idCms, $printable_cms_pages);
    }

    public function hookDisplayCMSDisputeInformation($params)
    {
        $idCms = (int) Tools::getValue('id_cms');

        if (empty($idCms)) {
            return;
        }

        $template = 'module:' . $this->name . '/views/templates/hook/hookDisplayCMSDisputeInformation.tpl';
        $cacheId = $this->getCacheId($this->name . '|hookDisplayCMSDisputeInformation|' . $idCms);

        if (!$this->isCached($template, $cacheId)) {
            $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
            $cms_page_associated = $cms_role_repository->findOneByName(Roles::NOTICE);

            $isAssociated = false;

            if (
                ($cms_page_associated instanceof CMSRole)
                && $cms_page_associated->id_cms
                && $idCms == $cms_page_associated->id_cms
            ) {
                $isAssociated = true;
            }

            $this->smarty->assign([
                'isAssociated' => $isAssociated,
            ]);
        }

        return $this->fetch($template, $cacheId);
    }

    public function hookTermsAndConditions($param)
    {
        $returnedTermsAndConditions = [];

        $cmsRepository = $this->entity_manager->getRepository('CMS');
        $cmsRoleRepository = $this->entity_manager->getRepository('CMSRole');
        $cmsPageConditionsAssoiciated = $cmsRoleRepository->findOneByName(Roles::CONDITIONS);
        $cmsPageRevocationAssociated = $cmsRoleRepository->findOneByName(Roles::REVOCATION);
        $cmsPagePrivacyAssociated = $cmsRoleRepository->findOneByName(Roles::PRIVACY);

        $idShop = (int) $this->context->shop->id;
        $idLang = (int) $this->context->language->id;

        if (
            Configuration::get('PS_CONDITIONS')
            && (int) $cmsPageConditionsAssoiciated->id_cms > 0
            && (int) $cmsPageRevocationAssociated->id_cms > 0
        ) {
            $cmsConditions = $cmsRepository->i10nFindOneById(
                (int) $cmsPageConditionsAssoiciated->id_cms,
                $idLang,
                $idShop
            );

            $link_conditions = $cmsConditions
                ? $this->context->link->getCMSLink(
                    $cmsConditions,
                    $cmsConditions->link_rewrite,
                    (bool) Configuration::get('PS_SSL_ENABLED')
                )
                : null;

            $cmsRevocation = $cmsRepository->i10nFindOneById(
                (int) $cmsPageRevocationAssociated->id_cms,
                $idLang,
                $idShop
            );

            $link_revocation = $this->context->link->getCMSLink(
                $cmsRevocation,
                $cmsRevocation->link_rewrite,
                (bool) Configuration::get('PS_SSL_ENABLED')
            );

            $cms_privacy = $cmsRepository->i10nFindOneById(
                (int) $cmsPagePrivacyAssociated->id_cms,
                $idLang,
                $idShop
            );

            $termsAndConditions = new TermsAndConditions();
            $termsAndConditions->setIdentifier('terms-and-conditions');

            if (!Configuration::get('AEUC_LABEL_COND_PRIVACY')) {
                $tpl = $this->context->smarty->createTemplate(
                    _PS_MODULE_DIR_ . $this->name . '/views/templates/front/terms_and_condition_revocation.tpl',
                    $this->context->smarty
                );

                // hide the checkbox is the option is disabled
                $tpl->assign([
                    'checkbox_identifier' => 'terms-and-conditions',
                ]);

                $termsAndConditions->setText(
                    $this->trans(
                        'Please note our [%terms_and_conditions%] and [%revocation%]',
                        [
                            '%revocation%' => $cmsRevocation->meta_title,
                            '%terms_and_conditions%' => $cmsConditions->meta_title,
                        ],
                        'Modules.Legalcompliance.Shop'
                    ) . $tpl->fetch(),
                    $link_conditions,
                    $link_revocation
                );
            } else {
                $link_privacy = $this->context->link->getCMSLink(
                    $cms_privacy,
                    $cms_privacy->link_rewrite,
                    (bool) Configuration::get('PS_SSL_ENABLED')
                );

                $termsAndConditions->setText(
                    $this->trans('I agree to the [terms of service], [revocation terms] and [privacy terms] and will adhere to them unconditionally.', [], 'Modules.Legalcompliance.Shop') ,
                    $link_conditions,
                    $link_revocation,
                    $link_privacy
                );
            }

            $returnedTermsAndConditions[] = $termsAndConditions;
        }

        if (
            Configuration::get('AEUC_LABEL_REVOCATION_VP')
            && VirtualCart::hasCartVirtualProduct($this->context->cart)
        ) {
            $termsAndConditions = new TermsAndConditions();

            $translation = $this->trans(
                '[1]For digital goods:[/1] I want immediate access to the digital content and I acknowledge that thereby I lose my right to cancel once the service has begun.[2][1]For services:[/1] I agree to the starting of the service and I acknowledge that I lose my right to cancel once the service has been fully performed.', [], 'Modules.Legalcompliance.Shop'
            );

            $termsAndConditions
                ->setText(
                    str_replace(
                        ['[1]', '[/1]', '[2]'],
                        ['<strong>', '</strong>', '<br>'],
                        $translation
                    )
                )
                ->setIdentifier('virtual-products');

            $returnedTermsAndConditions[] = $termsAndConditions;
        }

        if (count($returnedTermsAndConditions) > 0) {
            return $returnedTermsAndConditions;
        } else {
            return false;
        }
    }

    public function hookDisplayCMSPrintButton($param)
    {
        $idCms = (int) Tools::getValue('id_cms');
        $contentOnly = (bool) Tools::getValue('content_only');

        $template = 'module:' . $this->name . '/views/templates/hook/hookDisplayCMSPrintButton.tpl';
        $cacheId = $this->getCacheId($this->name . '|hookDisplayCMSPrintButton|' . $idCms . '|' . $contentOnly);

        if (!$this->isCached($template, $cacheId)) {
            $showButton = true;

            if (!$this->isPrintableCMSPage($idCms)) {
                $showButton = false;
            }

            $cms_repository = $this->entity_manager->getRepository('CMS');
            $cms_current = $cms_repository->i10nFindOneById(
                $idCms,
                (int) $this->context->language->id,
                (int) $this->context->shop->id
            );

            $cms_current_link = $this->context->link->getCMSLink(
                $cms_current,
                $cms_current->link_rewrite,
                (bool) Configuration::get('PS_SSL_ENABLED')
            );

            if (!strpos($cms_current_link, '?')) {
                $cms_current_link .= '?direct_print=1';
            } else {
                $cms_current_link .= '&direct_print=1';
            }

            $this->smarty->assign([
                'print_link' => $cms_current_link,
                'directPrint' => $contentOnly,
                'showButton' => $showButton,
            ]);
        }

        return $this->fetch($template, $cacheId);
    }

    public function hookDisplayProductPriceBlock($param)
    {
        if (
            empty($param['product'])
            || empty($param['type'])
            || !in_array($param['type'], [
                    'before_price',
                    'old_price',
                    'price',
                    'after_price',
                    'list_taxes',
                    'unit_price'
                ])
        ) {
            return '';
        }

        $product = $param['product'];
        $type = $param['type'];

        $cache_key = $this->name . '|' . $type . '|' . $product['id_product'];

        $cache_id = $this->getCacheId($cache_key);
        $template = 'module:' . $this->name . '/views/templates/hook/hookDisplayProductPriceBlock_' . $type . '.tpl';

        if (!$this->isCached($template, $cache_id)) {
            $smartyVars = [];

            /* Handle Product Combinations label */
            if (
                $type == 'before_price'
                && Configuration::get('AEUC_LABEL_COMBINATION_FROM')
                && !empty($product['attributes'])
            ) {
                $need_display = false;

                $product_instance = new Product($product['id_product']);
                $combinations = $product_instance->getAttributeCombinations($this->context->language->id);

                if ($combinations && is_array($combinations)) {
                    foreach ($combinations as $combination) {
                        if ((float) $combination['price'] != 0) {
                            $need_display = true;
                            break;
                        }
                    }

                    unset($combinations);

                    if ($need_display) {
                        $smartyVars['before_price'] = [];
                        $smartyVars['before_price']['from_str_i18n'] = $this->trans('From', [], 'Modules.Legalcompliance.Shop');
                    }
                }
            }

            /* Handle Specific Price label*/
            if (
                $type == 'old_price'
                && Configuration::get('AEUC_LABEL_SPECIFIC_PRICE')
            ) {
                $smartyVars['old_price'] = [];
                $smartyVars['old_price']['before_str_i18n'] = $this->trans('Our previous price', [], 'Modules.Legalcompliance.Shop');
            }

            /* Handle Shipping Inc./Exc.*/
            if ($type == 'price') {
                $smartyVars['price'] = [];

                if (Configuration::get('AEUC_LABEL_SHIPPING_INC_EXC')) {
                    if (!$product['is_virtual']) {
                        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
                        $cms_repository = $this->entity_manager->getRepository('CMS');
                        $cms_page_associated = $cms_role_repository->findOneByName(Roles::SHIP_PAY);

                        if (isset($cms_page_associated->id_cms) && $cms_page_associated->id_cms != 0) {
                            $cms_ship_pay_id = (int) $cms_page_associated->id_cms;
                            $cms_ship_pay = $cms_repository->i10nFindOneById(
                                $cms_ship_pay_id,
                                $this->context->language->id,
                                $this->context->shop->id
                            );
                            $is_ssl_enabled = (bool) Configuration::get('PS_SSL_ENABLED');
                            $link_ship_pay = $this->context->link->getCMSLink($cms_ship_pay, $cms_ship_pay->link_rewrite, $is_ssl_enabled);

                            $smartyVars['ship'] = [];
                            $smartyVars['ship']['link_ship_pay'] = $link_ship_pay;
                            $smartyVars['ship']['ship_str_i18n'] = $this->trans('Shipping excluded', [], 'Modules.Legalcompliance.Shop');
                        }
                    } elseif (
                        $product['is_virtual']
                        && Configuration::get('AEUC_VP_ACTIVE')
                    ) {
                        $cms_ship_pay_id = (int) Configuration::get('AEUC_VP_CMS_ID');

                        if ($cms_ship_pay_id) {
                            $cms_ship_pay = $this->entity_manager
                                ->getRepository('CMS')
                                ->i10nFindOneById($cms_ship_pay_id, $this->context->language->id, $this->context->shop->id);

                            $smartyVars['ship'] = array(
                                'link_ship_pay' => $this->context->link->getCMSLink(
                                        $cms_ship_pay,
                                        $cms_ship_pay->link_rewrite,
                                        (bool) Configuration::get('PS_SSL_ENABLED')
                                    ),
                                'ship_str_i18n' => Configuration::get('AEUC_VP_LABEL_TEXT', $this->context->language->id)
                            );
                        }
                    } else {
                        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
                        $cms_repository = $this->entity_manager->getRepository('CMS');
                        $cms_page_associated = $cms_role_repository->findOneByName(Roles::SHIP_PAY);

                        if (isset($cms_page_associated->id_cms) && !$cms_page_associated->id_cms) {
                            $cms_ship_pay_id = (int) $cms_page_associated->id_cms;
                            $cms_ship_pay = $cms_repository->i10nFindOneById(
                                $cms_ship_pay_id,
                                $this->context->language->id,
                                $this->context->shop->id
                            );

                            $is_ssl_enabled = (bool) Configuration::get('PS_SSL_ENABLED');
                            $link_ship_pay = $this->context->link->getCMSLink($cms_ship_pay, $cms_ship_pay->link_rewrite, $is_ssl_enabled);

                            $smartyVars['ship'] = [];
                            $smartyVars['ship']['link_ship_pay'] = $link_ship_pay;
                            $smartyVars['ship']['ship_str_i18n'] = $this->trans('Download Info', [], 'Modules.Legalcompliance.Shop');
                        }
                    }
                }
            }

            /* Handle Delivery time label */
            if ($type == 'after_price') {
                $smartyVars['after_price'] = [];

                if ((int) Configuration::get('AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL') == 1) {
                    $delivery_addtional_info = Configuration::get('AEUC_LABEL_DELIVERY_ADDITIONAL', (int) $this->context->language->id);
                    if (trim($delivery_addtional_info) !== '') {
                        $smartyVars['after_price']['delivery_str_i18n'] = '*';
                    }
                }
            }

            /* Handle Taxes Inc./Exc.*/
            if ($type == 'list_taxes') {
                $smartyVars['list_taxes'] = [];

                if (Configuration::get('AEUC_LABEL_TAX_INC_EXC')) {
                    $customer_default_group_id = (int) $this->context->customer->id_default_group;
                    $customer_default_group = new Group($customer_default_group_id);

                    if (
                        Configuration::get('PS_TAX')
                        && $this->context->country->display_tax_label
                        && !(
                            Validate::isLoadedObject($customer_default_group)
                            && $customer_default_group->price_display_method
                        )
                    ) {
                        $smartyVars['list_taxes']['tax_str_i18n'] = $this->trans('Tax included', [], 'Shop.Theme.Checkout');
                    } else {
                        $smartyVars['list_taxes']['tax_str_i18n'] = $this->trans('Tax excluded', [], 'Shop.Theme.Checkout');
                    }
                }
            }

            /* Handle Unit prices */
            if ($type == 'unit_price') {
                $unit_price = $product['unit_price_tax_included'] ?? 0;

                if (Configuration::get('AEUC_LABEL_UNIT_PRICE') && $unit_price > 0) {
                    $smartyVars['unit_price'] = (new \PrestaShop\PrestaShop\Adapter\Product\PriceFormatter)
                    ->format($unit_price) . ' ' . $product['unity'];

                    if (Module::isEnabled('gc_unitprice')) {
                        /** @var GC_Unitprice $gc_unitprice */
                        $gc_unitprice = Module::getInstanceByName('gc_unitprice');
                        $smartyVars['unit_price'] = $gc_unitprice->getFullUnitPrice($smartyVars['unit_price'], $product->unity);
                    }
                }
            }

            $this->smarty->assign([
                'smartyVars' => $smartyVars,
            ]);
        }

        return $this->fetch($template, $cache_id);
    }

    public function hookDisplayCheckoutSubtotalDetails($param)
    {
        if (
            'shipping' !== $param['subtotal']['type']
            || 0 !== $param['subtotal']['amount']
        ) {
            return;
        }

        $template = 'module:' . $this->name . '/views/templates/hook/hookDisplayCartPriceBlock_shipping_details.tpl';
        $cacheId = $this->getCacheId($this->name . '|hookDisplayCheckoutSubtotalDetails');

        if (!$this->isCached($template, $cacheId)) {
            $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
            $cms_page_shipping_and_payment = $cms_role_repository->findOneByName(Roles::SHIP_PAY);
            $link_shipping_payment = $this->context->link->getCMSLink((int) $cms_page_shipping_and_payment->id_cms);

            $this->smarty->assign([
                'link_shipping_payment' => $link_shipping_payment,
            ]);
        }

        return $this->fetch($template, $cacheId);
    }

    private function postProcess()
    {
        if (Tools::isSubmit('submitCheckForNewTemplates')) {
            $emailTemplatesInserted = $this->processCheckForNewTemplates();

            if ($emailTemplatesInserted) {
                $this->_confirmations[] = $this->trans('%count% email templates were installed successfully.', ['%count%' => $emailTemplatesInserted], 'Modules.Pslegalcompliance.Admin');
            } else {
                $this->_errors[] = $this->trans('No new email templates were found.', [], 'Modules.Pslegalcompliance.Admin');
            }
        }
    }

    private function processCheckForNewTemplates(): int
    {
        $emailTemplates = $this->getNewEmailTemplates();
        $this->insertEmailTemplates($emailTemplates);

        return count($emailTemplates);
    }

    private function getNewEmailTemplates(): array
    {
        $emailTemplateFinder = new EmailTemplateFinder($this->email);

        return $emailTemplateFinder->findNewEmailTemplates();
    }

    private function insertEmailTemplates(array $email_templates)
    {
        foreach ($email_templates as $mail) {
            $new_email = new AeucEmailEntity();
            $new_email->filename = (string) $mail;
            $new_email->display_name = $this->email->getCleanedMailName($mail);
            $new_email->save();

            unset($new_email);
        }
    }

    /**
     * Load the configuration form.
     */
    public function getContent()
    {
        $this->postProcess();

        $success_band = $this->_postProcess();

        $this->context->smarty->assign('module_dir', $this->_path);
        $this->context->smarty->assign('errors', $this->_errors);
        $this->context->controller->addCSS($this->_path . 'views/css/configure.css', 'all');
        // Render all required form for each 'part'
        $formLabelsManager = $this->renderFormLabelsManager();
        $formVirtualProductsManager = $this->renderFormVirtualProductsManager();
        $formFeaturesManager = $this->renderFormFeaturesManager();
        $formLegalContentManager = $this->renderFormLegalContentManager();
        $formEmailAttachmentsManager = $this->renderFormEmailAttachmentsManager();
        $formLegalMailFooter = $this->renderFormLegalMailFooter();

        return $success_band
            . $formLabelsManager
            . $formVirtualProductsManager
            . $formFeaturesManager
            . $formLegalContentManager
            . $formEmailAttachmentsManager
            . $formLegalMailFooter
        ;
    }

    /**
     * Save form data.
     */
    protected function _postProcess()
    {
        $has_processed_something = false;

        $post_keys_switchable = array_keys(array_merge(
            $this->getConfigFormLabelsManagerValues(),
            $this->getConfigFormVirtualProductsManagerValues(),
            $this->getConfigFormFeaturesManagerValues()
        ));

        $post_keys_complex = [
            'AEUC_legalContentManager',
            'AEUC_emailAttachmentsManager',
            'discard_tpl_warn',
            'submitLegalMailFooter',
        ];

        $i10n_inputs_received = [];
        $received_values = Tools::getAllValues();

        foreach (array_keys($received_values) as $key_received) {
            /* Case its one of form with only switches in it */
            if (in_array($key_received, $post_keys_switchable)) {
                $is_option_active = Tools::getValue($key_received);
                $key = Tools::strtolower($key_received);
                $key = Tools::toCamelCase($key);

                if (method_exists($this, 'process' . $key)) {
                    $this->{'process' . $key}($is_option_active);
                    $has_processed_something = true;
                }

                continue;
            }
            /* Case we are on more complex forms */
            if (in_array($key_received, $post_keys_complex)) {
                // Clean key
                $key = Tools::strtolower($key_received);
                $key = Tools::toCamelCase($key, true);

                if (method_exists($this, 'process' . $key)) {
                    $this->{'process' . $key}();
                    $has_processed_something = true;
                }
            }

            /* Case Multi-lang input */
            if (strripos($key_received, 'AEUC_LABEL_CUSTOM_CART_TEXT') !== false) {
                $exploded = explode('_', $key_received);
                $count = count($exploded);
                $id_lang = (int) $exploded[$count - 1];
                $i10n_inputs_received['AEUC_LABEL_CUSTOM_CART_TEXT'][$id_lang] = $received_values[$key_received];
            }

            if (strripos($key_received, 'AEUC_LABEL_DELIVERY_ADDITIONAL') !== false) {
                $exploded = explode('_', $key_received);
                $count = count($exploded);
                $id_lang = (int) $exploded[$count - 1];
                $i10n_inputs_received['AEUC_LABEL_DELIVERY_ADDITIONAL'][$id_lang] = $received_values[$key_received];
            }

            if (strripos($key_received, 'AEUC_VP_LABEL_TEXT') !== false) {
                $exploded = explode('_', $key_received);
                $count = count($exploded);
                $id_lang = (int) $exploded[$count - 1];
                $i10n_inputs_received['AEUC_VP_LABEL_TEXT'][$id_lang] = $received_values[$key_received];
            }
        }

        if (count($i10n_inputs_received) > 0) {
            $this->processAeucLabelMultiLang($i10n_inputs_received);
            $this->processAeucVirtualProductsMultiLang($i10n_inputs_received);

            $has_processed_something = true;
        }

        if ($has_processed_something) {
            $this->_clearCache('*');

            return (count($this->_errors) ? $this->displayError($this->_errors) : '')
                . (count($this->_warnings) ? $this->displayWarning($this->_warnings) : '')
                . $this->displayConfirmation($this->trans('The settings have been updated.', [], 'Admin.Notifications.Success'))
            ;
        } else {
            return (count($this->_errors) ? $this->displayError($this->_errors) : '')
                . (count($this->_warnings) ? $this->displayWarning($this->_warnings) : '')
            ;
        }
    }

    protected function processAeucLabelMultiLang(array $i10n_inputs)
    {
        if (isset($i10n_inputs['AEUC_LABEL_DELIVERY_ADDITIONAL'])) {
            Configuration::updateValue('AEUC_LABEL_DELIVERY_ADDITIONAL', $i10n_inputs['AEUC_LABEL_DELIVERY_ADDITIONAL']);
        }

        if (isset($i10n_inputs['AEUC_LABEL_CUSTOM_CART_TEXT'])) {
            Configuration::updateValue('AEUC_LABEL_CUSTOM_CART_TEXT', $i10n_inputs['AEUC_LABEL_CUSTOM_CART_TEXT']);
        }
    }

    protected function processAeucVirtualProductsMultiLang(array $i10n_inputs)
    {
        if (isset($i10n_inputs['AEUC_VP_LABEL_TEXT'])) {
            Configuration::updateValue('AEUC_VP_LABEL_TEXT', $i10n_inputs['AEUC_VP_LABEL_TEXT']);
        }
    }

    protected function processAeucLabelCombinationFrom($is_option_active)
    {
        Configuration::updateValue('AEUC_LABEL_COMBINATION_FROM', (bool) $is_option_active);
    }

    protected function processAeucLabelSpecificPrice($is_option_active)
    {
        Configuration::updateValue('AEUC_LABEL_SPECIFIC_PRICE', (bool) $is_option_active);
    }

    protected function processAeucEmailAttachmentsManager()
    {
        $json_attach_assoc = json_decode(Tools::getValue('emails_attach_assoc'));

        if (!$json_attach_assoc) {
            return;
        }

        // Empty previous assoc to make new ones
        AeucCMSRoleEmailEntity::truncate();
        $pdf_attachment = [];

        foreach ($json_attach_assoc as $assoc) {
            if ($assoc->id_mail == 'pdf') {
                $pdf_attachment[] = (int) $assoc->id_cms_role;
                continue;
            }

            $assoc_obj = new AeucCMSRoleEmailEntity();
            $assoc_obj->id_mail = $assoc->id_mail;
            $assoc_obj->id_cms_role = $assoc->id_cms_role;

            if (!$assoc_obj->save()) {
                $this->_errors[] = $this->trans('Failed to associate legal content with an email template.', [], 'Modules.Legalcompliance.Admin');
            }
        }

        // save PDF Attachment
        Configuration::updateValue('AEUC_PDF_ATTACHMENT', serialize($pdf_attachment));
    }

    protected function processAeucLabelRevocationTOS($is_option_active)
    {
        // Check first if LEGAL_REVOCATION CMS Role has been set before doing anything here
        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
        $cms_page_associated = $cms_role_repository->findOneByName(Roles::REVOCATION);
        $cms_roles = $this->getCMSRoles();

        if ($is_option_active) {
            if (
                !($cms_page_associated instanceof CMSRole)
                || !$cms_page_associated->id_cms
            ) {
                $this->_errors[] =
                    $this->trans(
                        '\'Revocation Terms within ToS\' label cannot be activated unless you associate "%s" role with a Page.',
                        [
                            '%s' => (string) $cms_roles[Roles::REVOCATION],
                        ],
                        'Modules.Legalcompliance.Admin'
                    );

                return;
            }
            Configuration::updateValue('AEUC_LABEL_REVOCATION_TOS', true);
        } else {
            Configuration::updateValue('AEUC_LABEL_REVOCATION_TOS', false);
        }
    }

    protected function processaeucVpActive($is_option_active)
    {
        Configuration::updateValue('AEUC_VP_ACTIVE', (bool) $is_option_active);
    }

    protected function processAeucLabelCondPrivacy($is_option_active)
    {
        Configuration::updateValue('AEUC_LABEL_COND_PRIVACY', (bool) $is_option_active);
    }

    protected function processAeucLabelRevocationVP($is_option_active)
    {
        Configuration::updateValue('AEUC_LABEL_REVOCATION_VP', (bool) $is_option_active);
    }

    protected function processaeucVpCmsId($id_cms)
    {
        Configuration::updateValue('AEUC_VP_CMS_ID', (int) $id_cms);
    }

    protected function processAeucLabelShippingIncExc($is_option_active)
    {
        // Check first if LEGAL_SHIP_PAY CMS Role has been set before doing anything here
        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
        $cms_page_associated = $cms_role_repository->findOneByName(Roles::SHIP_PAY);
        $cms_roles = $this->getCMSRoles();

        if ($is_option_active) {
            if (
                !($cms_page_associated instanceof CMSRole)
                || !$cms_page_associated->id_cms
            ) {
                $this->_errors[] =
                    $this->trans(
                        'Shipping fees label cannot be activated unless you associate "%s" role with a Page.',
                        array(
                            '%s' => (string) $cms_roles[Roles::SHIP_PAY],
                        ),
                        'Modules.Legalcompliance.Admin'
                    );

                return;
            }

            Configuration::updateValue('AEUC_LABEL_SHIPPING_INC_EXC', true);
        } else {
            Configuration::updateValue('AEUC_LABEL_SHIPPING_INC_EXC', false);
        }
    }

    protected function processAeucLabelTaxIncExc($is_option_active)
    {
        $countries = Country::getCountries((int) $this->context->language->id, true);

        foreach ($countries as $id_country => $country_row) {
            $country = new Country($id_country);
            $country->display_tax_label = (bool) $is_option_active;
            $country->save();
        }

        Configuration::updateValue('AEUC_LABEL_TAX_INC_EXC', (bool) $is_option_active);
    }

    protected function processAeucLabelUnitPrice($is_option_active)
    {
        Configuration::updateValue('AEUC_LABEL_UNIT_PRICE', $is_option_active);
    }

    protected function processPsAtcpShipWrap($is_option_active)
    {
        Configuration::updateValue('PS_ATCP_SHIPWRAP', $is_option_active);
    }

    protected function processAeucFeatReorder($is_option_active)
    {
        Configuration::updateValue('PS_DISALLOW_HISTORY_REORDERING', !$is_option_active);
    }

    protected function processAeucLegalContentManager()
    {
        $posted_values = Tools::getAllValues();
        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');

        foreach ($posted_values as $key_name => $assoc_cms_id) {
            if (strpos($key_name, 'CMSROLE_') !== false) {
                $exploded_key_name = explode('_', $key_name);
                $cms_role = $cms_role_repository->findOne((int) $exploded_key_name[1]);
                $cms_role->id_cms = (int) $assoc_cms_id;
                $cms_role->update();
            }
        }

        unset($cms_role);

        Configuration::updateValue('AEUC_LINKBLOCK_FOOTER', (int) Tools::getValue('AEUC_LINKBLOCK_FOOTER'));
    }

    protected function processAeucLabelTaxFooter($is_option_active)
    {
        Configuration::updateValue('AEUC_LABEL_TAX_FOOTER', (bool) $is_option_active);
    }

    protected function processSubmitlegalmailfooter()
    {
        if (Tools::isSubmit('submitLegalMailFooter')) {
            $LEGAL_MAIL_FOOTER = [];

            foreach ($this->context->controller->getLanguages() as $lang) {
                $LEGAL_MAIL_FOOTER[$lang['id_lang']] = Tools::getValue('LEGAL_MAIL_FOOTER_' . $lang['id_lang'], null);
            }

            Configuration::updateValue('LEGAL_MAIL_FOOTER', $LEGAL_MAIL_FOOTER, true);
        }
    }

    protected function processAeucLabelDisplayDeliveryAdditional($is_option_active)
    {
        Configuration::updateValue('AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL', (int) $is_option_active);
    }

    public function getCMSRoles()
    {
        return [
            Roles::NOTICE => $this->trans('Legal notice', [], 'Modules.Legalcompliance.Admin'),
            Roles::CONDITIONS => $this->trans('Terms of Service (ToS)', [], 'Modules.Legalcompliance.Admin'),
            Roles::REVOCATION => $this->trans('Revocation terms', [], 'Modules.Legalcompliance.Admin'),
            Roles::REVOCATION_FORM => $this->trans('Revocation form', [], 'Modules.Legalcompliance.Admin'),
            Roles::PRIVACY => $this->trans('Privacy', [], 'Modules.Legalcompliance.Admin'),
            Roles::ENVIRONMENTAL => $this->trans('Environmental notice', [], 'Modules.Legalcompliance.Admin'),
            Roles::SHIP_PAY => $this->trans('Shipping and payment', [], 'Modules.Legalcompliance.Admin'),
        ];
    }

    /**
     * Create the form that will let user choose all the wording options.
     */
    protected function renderFormLabelsManager()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAEUC_labelsManager';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name
            . '&token=' . Tools::getAdminTokenLite('AdminModules')
        ;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormLabelsManagerValues(),
            /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        // Insert JS in the page
        $this->context->controller->addJS($this->_path . 'views/js/admin.js');

        return $helper->generateForm([$this->getConfigFormLabelsManager()]);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigFormLabelsManager()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Labels', [], 'Modules.Legalcompliance.Admin'),
                    'icon' => 'icon-tags',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Additional information about delivery time', [], 'Modules.Legalcompliance.Admin'),
                        'name' => 'AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL',
                        'is_bool' => true,
                        'desc' => $this->trans('If you specified a delivery time...', [], 'Modules.Legalcompliance.Admin'),
                        'values' => [
                            [
                                'id' => 'AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL_ON',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL_OFF',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'lang' => true,
                        'label' => '',
                        'name' => 'AEUC_LABEL_DELIVERY_ADDITIONAL',
                        'desc' => $this->trans('If you specified a delivery time, this additional information is displayed in the footer of product pages with a link to the "Shipping & Payment" Page. Leave the field empty to disable.', [], 'Modules.Legalcompliance.Admin'),
                        'hint' => $this->trans('Indicate for which countries your delivery time applies.', [], 'Modules.Legalcompliance.Admin'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans(' \'Our previous price\' label', [], 'Modules.Legalcompliance.Admin'),
                        'name' => 'AEUC_LABEL_SPECIFIC_PRICE',
                        'is_bool' => true,
                        'desc' => $this->trans('When a product is on sale, displays a \'Our previous price\' label before the original price crossed out, next to the price on the product page.', [], 'Modules.Legalcompliance.Admin'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Tax \'inc./excl.\' label', [], 'Modules.Legalcompliance.Admin'),
                        'name' => 'AEUC_LABEL_TAX_INC_EXC',
                        'is_bool' => true,
                        'desc' => $this->trans('Displays whether the tax is included on the product page (\'Tax incl./excl.\' label) and adds a short mention in the footer of other pages.', [], 'Modules.Legalcompliance.Admin'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Admin.Global'),
                            ],
                            ['id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Price per unit label', [], 'Modules.Legalcompliance.Admin'),
                        'name' => 'AEUC_LABEL_UNIT_PRICE',
                        'is_bool' => true,
                        'desc' => $this->trans('If available, displays the price per unit everywhere the product price is listed.', [], 'Modules.Legalcompliance.Admin'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('\'Shipping fees excl.\' label', [], 'Modules.Legalcompliance.Admin'),
                        'name' => 'AEUC_LABEL_SHIPPING_INC_EXC',
                        'is_bool' => true,
                        'desc' => $this->trans('Displays a label next to the product price (\'Shipping excluded\') and adds a short mention in the footer of other pages.', [], 'Modules.Legalcompliance.Admin'),
                        'hint' => $this->trans('If enabled, make sure the Shipping terms are associated with a page below (Legal Content Management). The label will link to this content.', [], 'Modules.Legalcompliance.Admin'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Show Conditions Checkbox', [], 'Modules.Legalcompliance.Admin'),
                        'name' => 'AEUC_LABEL_COND_PRIVACY',
                        'is_bool' => true,
                        'desc' => $this->trans('Shows a checkbox to confirm conditions privacy and revocation (default: Yes)', [], 'Modules.Legalcompliance.Admin'),
                        'disable' => false,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Revocation Terms within ToS', [], 'Modules.Legalcompliance.Admin'),
                        'name' => 'AEUC_LABEL_REVOCATION_TOS',
                        'is_bool' => true,
                        'desc' => $this->trans('Includes content from the Revocation Terms page within the Terms of Services (ToS).', [], 'Modules.Legalcompliance.Admin'),
                        'hint' => $this->trans('If enabled, make sure the Revocation Terms are associated with a page below (Legal Content Management).', [], 'Modules.Legalcompliance.Admin'),
                        'disable' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('\'From\' price label (when combinations)', [], 'Modules.Legalcompliance.Admin'),
                        'name' => 'AEUC_LABEL_COMBINATION_FROM',
                        'is_bool' => true,
                        'desc' => $this->trans('Displays a \'From\' label before the price on products with combinations.', [], 'Modules.Legalcompliance.Admin'),
                        'hint' => $this->trans('As prices can vary from a combination to another, this label indicates that the final price may be higher.', [], 'Modules.Legalcompliance.Admin'),
                        'disable' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->trans('Custom text in shopping cart page', [], 'Modules.Legalcompliance.Admin'),
                        'name' => 'AEUC_LABEL_CUSTOM_CART_TEXT',
                        'desc' => $this->trans('This text will be displayed on the shopping cart page. Leave empty to disable.', [], 'Modules.Legalcompliance.Admin'),
                        'hint' => $this->trans('Please inform your customers about how the order is legally confirmed.', [], 'Modules.Legalcompliance.Admin'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Display tax in footer', [], 'Modules.Legalcompliance.Admin'),
                        'name' => 'AEUC_LABEL_TAX_FOOTER',
                        'is_bool' => true,
                        'desc' => $this->trans('Displays the tax informations in the footer.', [], 'Modules.Legalcompliance.Admin'),
                        'disable' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormLabelsManagerValues()
    {
        $custom_cart_text_values = [];

        foreach (Language::getLanguages(false, false) as $lang) {
            $tmp_id_lang = (int) $lang['id_lang'];
            $delivery_additional[$tmp_id_lang] = Configuration::get('AEUC_LABEL_DELIVERY_ADDITIONAL', $tmp_id_lang);
            $custom_cart_text_values[$tmp_id_lang] = Configuration::get('AEUC_LABEL_CUSTOM_CART_TEXT', $tmp_id_lang);
        }

        return [
            'AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL' => (int) Configuration::get('AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL'),
            'AEUC_LABEL_DELIVERY_ADDITIONAL' => $delivery_additional,
            'AEUC_LABEL_CUSTOM_CART_TEXT' => $custom_cart_text_values,
            'AEUC_LABEL_SPECIFIC_PRICE' => Configuration::get('AEUC_LABEL_SPECIFIC_PRICE'),
            'AEUC_LABEL_UNIT_PRICE' => Configuration::get('AEUC_LABEL_UNIT_PRICE'),
            'AEUC_LABEL_TAX_INC_EXC' => Configuration::get('AEUC_LABEL_TAX_INC_EXC'),
            'AEUC_LABEL_COND_PRIVACY' => Configuration::get('AEUC_LABEL_COND_PRIVACY'),
            'AEUC_LABEL_REVOCATION_TOS' => Configuration::get('AEUC_LABEL_REVOCATION_TOS'),
            'AEUC_LABEL_SHIPPING_INC_EXC' => Configuration::get('AEUC_LABEL_SHIPPING_INC_EXC'),
            'AEUC_LABEL_COMBINATION_FROM' => Configuration::get('AEUC_LABEL_COMBINATION_FROM'),
            'AEUC_LABEL_TAX_FOOTER' => Configuration::get('AEUC_LABEL_TAX_FOOTER'),
        ];
    }

    public function renderFormVirtualProductsManager()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAEUC_virtualProductsManager';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name
        ;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormVirtualProductsManagerValues(),
            /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigFormVirtualProductsManager()]);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigFormVirtualProductsManager()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Virtual Products', [], 'Modules.Legalcompliance.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Label "Virtual Product"', [], 'Modules.Legalcompliance.Admin'),
                        'hint' => false,
                        'name' => 'AEUC_VP_ACTIVE',
                        'is_bool' => true,
                        'desc' => $this->trans('Show a label placed next to the product-tax and links to the virtual products CMS-Infopage', [], 'Modules.Legalcompliance.Admin'),
                        'values' => [
                            [
                                'id' => 'AEUC_VP_ACTIVE_on',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'AEUC_VP_ACTIVE_off',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Virtual Products CMS-Infopage', [], 'Modules.Legalcompliance.Admin'),
                        'name' => 'AEUC_VP_CMS_ID',
                        'default_value' => Configuration::getGlobalValue('AEUC_VP_CMS_ID'),
                        'options' => [
                            'query' => array_map(
                                function ($cms) {
                                    return [
                                        'id_cms' => $cms->id,
                                        'meta_title' => $cms->meta_title
                                    ];
                                },
                                $this->entity_manager->getRepository('CMS')->i10nFindAll(
                                    $this->context->language->id,
                                    $this->context->shop->id
                                )
                            ),
                            'id' => 'id_cms',
                            'name' => 'meta_title',
                            'default' => [
                                'value' => 0,
                                'label' => $this->trans('-- Select associated page --', [], 'Modules.Legalcompliance.Admin')
                            ],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'lang' => true,
                        'label' => $this->trans('Labeltext "Virtual Product"', [], 'Modules.Legalcompliance.Admin'),
                        'name' => 'AEUC_VP_LABEL_TEXT',
                        'desc' => $this->trans('Text for the label linked to the virtual products CMS-Infopage', [], 'Modules.Legalcompliance.Admin')
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Revocation for virtual products', [], 'Modules.Legalcompliance.Admin'),
                        'name' => 'AEUC_LABEL_REVOCATION_VP',
                        'is_bool' => true,
                        'desc' => $this->trans('Adds a mandatory checkbox when the cart contains a virtual product. Use it to ensure customers are aware that a virtual product cannot be returned.', [], 'Modules.Legalcompliance.Admin'),
                        'hint' => $this->trans('Require customers to renounce their revocation right when purchasing virtual products (digital goods or services).', [], 'Modules.Legalcompliance.Admin'),
                        'disable' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormVirtualProductsManagerValues()
    {
        $label_text = [];

        foreach (Language::getLanguages(false, false) as $lang) {
            $label_text[(int) $lang['id_lang']] = Configuration::get('AEUC_VP_LABEL_TEXT', (int) $lang['id_lang']);
        }

        return [
            'AEUC_VP_ACTIVE' => Configuration::get('AEUC_VP_ACTIVE'),
            'AEUC_VP_CMS_ID' => Configuration::get('AEUC_VP_CMS_ID'),
            'AEUC_VP_LABEL_TEXT' => $label_text,
            'AEUC_LABEL_REVOCATION_VP' => Configuration::get('AEUC_LABEL_REVOCATION_VP'),
        ];
    }

    /**
     * Create the form that will let user choose all the wording options.
     */
    protected function renderFormFeaturesManager()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitAEUC_featuresManager';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name
        ;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFormFeaturesManagerValues(),
            /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        ];

        return $helper->generateForm([$this->getConfigFormFeaturesManager()]);
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigFormFeaturesManager()
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Features', [], 'Modules.Legalcompliance.Admin'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Enable \'Reordering\' feature', [], 'Modules.Legalcompliance.Admin'),
                        'hint' => $this->trans('If enabled, the \'Reorder\' option allows customers to reorder in one click from their Order History page.', [], 'Modules.Legalcompliance.Admin'),
                        'name' => 'AEUC_FEAT_REORDER',
                        'is_bool' => true,
                        'desc' => $this->trans('Make sure you comply with your local legislation before enabling: it can be considered as unsolicited goods.', [], 'Modules.Legalcompliance.Admin'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Proportionate tax for shipping and wrapping', [], 'Modules.Legalcompliance.Admin'),
                        'name' => 'PS_ATCP_SHIPWRAP',
                        'is_bool' => true,
                        'desc' => $this->trans('When enabled, tax for shipping and wrapping costs will be calculated proportionate to taxes applying to the products in the cart.', [], 'Modules.Legalcompliance.Admin'),
                        'hint' => $this->trans('If active, your carriers\' shipping fees must be tax included! Make sure it is the case in the Shipping section.', [], 'Modules.Legalcompliance.Admin'),
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->trans('Enabled', [], 'Admin.Global'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->trans('Disabled', [], 'Admin.Global'),
                            ],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormFeaturesManagerValues()
    {
        return [
            'AEUC_FEAT_REORDER' => !Configuration::get('PS_DISALLOW_HISTORY_REORDERING'),
            'PS_ATCP_SHIPWRAP' => Configuration::get('PS_ATCP_SHIPWRAP'),
        ];
    }

    /**
     * Create the form that will let user manage his legal page trough "CMS" feature.
     */
    protected function renderFormLegalContentManager()
    {
        $cms_roles_aeuc = $this->getCMSRoles();
        $cms_repository = $this->entity_manager->getRepository('CMS');
        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
        $cms_roles = $cms_role_repository->findByName(array_keys($cms_roles_aeuc));
        $cms_roles_assoc = [];
        $id_lang = Context::getContext()->employee->id_lang;
        $id_shop = Context::getContext()->shop->id;

        foreach ($cms_roles as $cms_role) {
            $assoc_cms_name = $this->trans('-- Select associated page --', [], 'Modules.Legalcompliance.Admin');

            if ($cms_role->id_cms) {
                $cms_entity = $cms_repository->findOne((int) $cms_role->id_cms);

                if (Validate::isLoadedObject($cms_entity)) {
                    $assoc_cms_name = $cms_entity->meta_title[(int) $id_lang] ?? '';
                }
            }

            $cms_roles_assoc[(int) $cms_role->id] = [
                'id_cms' => (int) $cms_role->id_cms,
                'page_title' => (string) $assoc_cms_name,
                'role_title' => (string) $cms_roles_aeuc[$cms_role->name],
            ];
        }

        $cms_pages = $cms_repository->i10nFindAll($id_lang, $id_shop);
        $fake_object = new stdClass();
        $fake_object->id = 0;
        $fake_object->meta_title = $this->trans('-- Select associated page --', [], 'Modules.Legalcompliance.Admin');
        $cms_pages[-1] = $fake_object;

        unset($fake_object);

        $this->context->smarty->assign([
           'cms_roles_assoc' => $cms_roles_assoc,
           'cms_pages' => $cms_pages,
           'form_action' => $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name,
           'add_cms_link' => $this->context->link->getAdminLink('AdminCMS'),
           'AEUC_LINKBLOCK_FOOTER' => (int) Configuration::get('AEUC_LINKBLOCK_FOOTER')
        ]);

        return $this->display(__FILE__, 'views/templates/admin/legal_cms_manager_form.tpl');
    }

    protected function renderFormEmailAttachmentsManager()
    {
        $cms_roles_aeuc = $this->getCMSRoles();
        $cms_role_repository = $this->entity_manager->getRepository('CMSRole');
        $cms_roles_associated = $cms_role_repository->getCMSRolesAssociated();
        $legal_options = [];
        $cleaned_mails_names = [];

        foreach ($cms_roles_associated as $role) {
            $list_id_mail_assoc = AeucCMSRoleEmailEntity::getIdEmailFromCMSRoleId((int) $role->id);
            $clean_list = [];

            foreach ($list_id_mail_assoc as $list_id_mail_assoc) {
                $clean_list[] = $list_id_mail_assoc['id_mail'];
            }

            $legal_options[$role->name] = [
                'name' => $cms_roles_aeuc[$role->name],
                'id' => $role->id,
                'list_id_mail_assoc' => $clean_list,
            ];
        }

        foreach (AeucEmailEntity::getAll() as $email) {
            $cleaned_mails_names[] = $email;
        }

        $emailTemplates = $this->getNewEmailTemplates();

        $this->context->smarty->assign([
            'has_assoc' => $cms_roles_associated,
            'mails_available' => $cleaned_mails_names,
            'legal_options' => $legal_options,
            'form_action' => $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name,
            'check_new_templates_link' => $this->context->link->getAdminLink(
                'AdminModules',
                true,
                [],
                [
                    'configure' => $this->name,
                    'submitCheckForNewTemplates' => '1'
                ]
            ),
            'pdf_attachment' => $this->getPDFAttachmentOptions(),
            'emailTemplatesMissing' => $emailTemplates,
        ]);

        // Insert JS in the page
        $this->context->controller->addJS($this->_path . 'views/js/email_attachement.js');

        return $this->display(__FILE__, 'views/templates/admin/email_attachments_form.tpl');
    }

    protected function renderFormLegalMailFooter()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = 'configuration';
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitLegalMailFooter';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name
            . '&tab_module=' . $this->tab
            . '&module_name=' . $this->name
            . '&token=' . Tools::getAdminTokenLite('AdminModules')
        ;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $fields_value = [
            'LEGAL_MAIL_FOOTER' => []
        ];

        $languages = $this->context->controller->getLanguages();

        foreach ($languages as $lang) {
            $fields_value['LEGAL_MAIL_FOOTER'][$lang['id_lang']] = Tools::getValue(
                'LEGAL_MAIL_FOOTER_' . $lang['id_lang'],
                Configuration::get('LEGAL_MAIL_FOOTER', $lang['id_lang'])
            );
        }

        $helper->tpl_vars = [
            'fields_value' => $fields_value,
            'languages' => $languages,
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm([
            [
                'form' => [
                    'legend' => [
                        'title' => $this->trans('Legal Mail Footer', [], 'Modules.Legalcompliance.Admin'),
                        'icon' => 'icon-envelope'
                    ],
                    'input' => [
                        [
                            'type' => 'textarea',
                            'autoload_rte' => true,
                            'lang' => true,
                            'label' => $this->trans('Additional HTML in Mail-Templates', [], 'Modules.Legalcompliance.Admin'),
                            'name' => 'LEGAL_MAIL_FOOTER',
                            'desc' => $this->trans('You can add additional legal informations and links to other legal ressources into this text editor.', [], 'Modules.Legalcompliance.Admin')
                        ],
                    ],
                    'submit' => [
                        'title' => $this->trans('Save', [], 'Admin.Actions')
                    ],
                ],
            ],
        ]);
    }

    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    private function getPDFAttachmentOptions()
    {
        $pdf_attachment = unserialize(Configuration::get('AEUC_PDF_ATTACHMENT'));

        if (!is_array($pdf_attachment)) {
            $pdf_attachment = [];
        }

        return $pdf_attachment;
    }
}
