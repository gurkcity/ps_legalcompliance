<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\EmailTemplateFinder;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Roles;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Traits\ModuleHelperTrait;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Traits\ModuleLicenseTrait;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Traits\ModulePaymentTrait;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Traits\ModuleTrait;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\VirtualCart;
use PrestaShop\PrestaShop\Adapter\ServiceLocator;
use PrestaShop\PrestaShop\Core\Checkout\TermsAndConditions;
use PrestaShop\PrestaShop\Core\Email\EmailLister;
use PrestaShop\PrestaShop\Core\Foundation\Database\EntityManager;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class PS_Legalcompliance extends PaymentModule
{
    use ModuleTrait;
    use ModuleHelperTrait;
    use ModuleLicenseTrait;
    use ModulePaymentTrait;

    const GC_VERSION = '9.0.0';
    const GC_SUBVERSION = '39';

    public function __construct()
    {
        $this->version = '9.0.1';

        $this->name = 'ps_legalcompliance';

        $this->author = 'Gurkcity';

        $this->ps_versions_compliancy = [
            'min' => '8.1.0',
            'max' => _PS_VERSION_,
        ];

        $this->tab = 'front_office_features';

        $this->displayName = $this->trans('Legal Compliance', [], 'Modules.Pslegalcompliance.Admin');
        $this->displayNamePre = $this->trans('Legal', [], 'Modules.Pslegalcompliance.Admin');
        $this->displayNamePost = $this->trans('Compliance', [], 'Modules.Pslegalcompliance.Admin');
        $this->description = $this->trans('Keep on growing your business serenely, sell all over Europe while complying with the applicable e-commerce laws.', [], 'Modules.Pslegalcompliance.Admin');
        $this->description_full = $this->trans('Continue to cultivate the growth of your business with a sense of calm and peace of mind. Expand your reach across the diverse markets of Europe, offering your products or services successfully in all regions. Simultaneously, maintain a steadfast commitment to adhering to all relevant e-commerce regulations, ensuring a solid and legally sound foundation for your expansion. This approach allows you to focus serenely on the ongoing development of your enterprise while providing a trustworthy and compliant shopping experience for your customers throughout Europe.', [], 'Modules.Pslegalcompliance.Admin');

        parent::__construct();

        $this->initModule();
    }

    public function enablePost()
    {
        return Configuration::updateValue('PS_ATCP_SHIPWRAP', true);
    }

    public function disablePost()
    {
        return Configuration::updateValue('PS_ATCP_SHIPWRAP', false);
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

            $cms_role_repository = ServiceLocator::get(EntityManager::class)->getRepository('CMSRole');
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
            $cmsRepository = ServiceLocator::get(EntityManager::class)->getRepository('CMS');
            $cmsRoleRepository = ServiceLocator::get(EntityManager::class)->getRepository('CMSRole');
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

        $cms_role_repository = ServiceLocator::get(EntityManager::class)->getRepository('CMSRole');

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
        $cms_repo = ServiceLocator::get(EntityManager::class)->getRepository('CMS');
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

        $cms_repo = ServiceLocator::get(EntityManager::class)->getRepository('CMS');
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

        $idCms = (int) Tools::getValue('id_cms');

        if (
            $this->context->controller instanceof CMSController
            && $this->isPrintableCMSPage($idCms)
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
        $cms_role_repository = ServiceLocator::get(EntityManager::class)->getRepository('CMSRole');

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
            $cms_role_repository = ServiceLocator::get(EntityManager::class)->getRepository('CMSRole');
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

        $cmsRepository = ServiceLocator::get(EntityManager::class)->getRepository('CMS');
        $cmsRoleRepository = ServiceLocator::get(EntityManager::class)->getRepository('CMSRole');
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

            $cms_repository = ServiceLocator::get(EntityManager::class)->getRepository('CMS');
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
                        $cms_role_repository = ServiceLocator::get(EntityManager::class)->getRepository('CMSRole');
                        $cms_repository = ServiceLocator::get(EntityManager::class)->getRepository('CMS');
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
                            $cms_ship_pay = ServiceLocator::get(EntityManager::class)
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
                        $cms_role_repository = ServiceLocator::get(EntityManager::class)->getRepository('CMSRole');
                        $cms_repository = ServiceLocator::get(EntityManager::class)->getRepository('CMS');
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
            $cms_role_repository = ServiceLocator::get(EntityManager::class)->getRepository('CMSRole');
            $cms_page_shipping_and_payment = $cms_role_repository->findOneByName(Roles::SHIP_PAY);
            $link_shipping_payment = $this->context->link->getCMSLink((int) $cms_page_shipping_and_payment->id_cms);

            $this->smarty->assign([
                'link_shipping_payment' => $link_shipping_payment,
            ]);
        }

        return $this->fetch($template, $cacheId);
    }

    public function getNewEmailTemplates(): array
    {
        $emailTemplateFinder = ServiceLocator::get(EmailTemplateFinder::class);

        return $emailTemplateFinder->findNewEmailTemplates();
    }

    private function insertEmailTemplates(array $email_templates)
    {
        $emailLister = ServiceLocator::get(EmailLister::class);

        foreach ($email_templates as $mail) {
            $new_email = new AeucEmailEntity();
            $new_email->filename = (string) $mail;
            $new_email->display_name = $emailLister->getCleanedMailName($mail);
            $new_email->save();

            unset($new_email);
        }
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

    private function getPDFAttachmentOptions()
    {
        $pdf_attachment = unserialize(Configuration::get('AEUC_PDF_ATTACHMENT'));

        if (!is_array($pdf_attachment)) {
            $pdf_attachment = [];
        }

        return $pdf_attachment;
    }
}
