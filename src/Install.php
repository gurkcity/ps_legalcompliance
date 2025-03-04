<?php

namespace PSLegalcompliance;

use AeucCMSRoleEmailEntity;
use AeucEmailEntity;
use Configuration;
use Context;
use Country;
use Db;
use PrestaShop\PrestaShop\Core\Foundation\Database\EntityManager;
use PrestaShop\PrestaShop\Core\Email\EmailLister;
use PS_Legalcompliance;

class Install
{
    private $module;
    private $entityManager;
    private $emailLister;
    private $context;
    private $sql = [];

    public function __construct(
        PS_Legalcompliance $module,
        EntityManager $entityManager,
        EmailLister $emailLister,
        Context $context
    ) {
        $this->module = $module;
        $this->entityManager = $entityManager;
        $this->emailLister = $emailLister;
        $this->context = $context;

        $this->sql[_DB_PREFIX_ . 'aeuc_cmsrole_email'] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aeuc_cmsrole_email` (
            `id` int(11) NOT NULL AUTO_INCREMENT,
            `id_cms_role` int(11) NOT NULL,
            `id_mail` int(11) NOT NULL,
            PRIMARY KEY (`id`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;
        ';

        $this->sql[_DB_PREFIX_ . 'aeuc_email'] = '
            CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'aeuc_email` (
                `id_mail` int(11) NOT NULL AUTO_INCREMENT,
                `filename` varchar(64) NOT NULL,
                `display_name` varchar(64) NOT NULL,
                PRIMARY KEY (`id_mail`)
            ) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8;
        ';
    }

    public function install(): bool
    {
        return $this->installSql()
            && Configuration::updateValue('AEUC_LABEL_DELIVERY_ADDITIONAL', false)
            && Configuration::updateValue('AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL', 0)
            && Configuration::updateValue('AEUC_LABEL_SPECIFIC_PRICE', false)
            && Configuration::updateValue('AEUC_LABEL_UNIT_PRICE', true)
            && Configuration::updateValue('AEUC_LABEL_COND_PRIVACY', true)
            && Configuration::updateValue('AEUC_LABEL_REVOCATION_TOS', false)
            && Configuration::updateValue('AEUC_LABEL_REVOCATION_VP', true)
            && Configuration::updateValue('AEUC_LABEL_SHIPPING_INC_EXC', false)
            && Configuration::updateValue('AEUC_LABEL_COMBINATION_FROM', true)
            && Configuration::updateValue('PS_TAX_DISPLAY', true)
            && Configuration::updateValue('PS_FINAL_SUMMARY_ENABLED', true)
            && Configuration::updateValue('AEUC_LABEL_TAX_FOOTER', true)
            && Configuration::updateValue('AEUC_LINKBLOCK_FOOTER', 1)
            && Configuration::updateValue('PS_DISALLOW_HISTORY_REORDERING', false)
            && Configuration::updateValue('AEUC_LABEL_TAX_INC_EXC', true)
            && $this->module->registerHook('displayHeader')
            && $this->module->registerHook('displayProductPriceBlock')
            && $this->module->registerHook('displayCheckoutSubtotalDetails')
            && $this->module->registerHook('displayFooter')
            && $this->module->registerHook('displayFooterAfter')
            && $this->module->registerHook('actionEmailSendBefore')
            && $this->module->registerHook('actionEmailAddAfterContent')
            && $this->module->registerHook('displayCartTotalPriceLabel')
            && $this->module->registerHook('displayCMSPrintButton')
            && $this->module->registerHook('displayCMSDisputeInformation')
            && $this->module->registerHook('termsAndConditions')
            && $this->module->registerHook('displayOverrideTemplate')
            && $this->module->registerHook('displayCheckoutSummaryTop')
            && $this->module->registerHook('sendMailAlterTemplateVars')
            && $this->module->registerHook('displayReassurance')
            && $this->module->registerHook('actionAdminControllerSetMedia')
            && $this->setLegalContentToOrderMails()
        ;

        $countries = Country::getCountries((int) $this->context->language->id, true);

        foreach ($countries as $id_country => $country_row) {
            $country = new Country($id_country);
            $country->display_tax_label = true;
            $country->save();
        }
    }

    public function uninstall(): bool
    {
        return
            Configuration::deleteByName('AEUC_LABEL_DELIVERY_ADDITIONAL')
            && Configuration::deleteByName('AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL')
            && Configuration::deleteByName('AEUC_LABEL_SPECIFIC_PRICE')
            && Configuration::deleteByName('AEUC_LABEL_UNIT_PRICE')
            && Configuration::deleteByName('AEUC_LABEL_TAX_INC_EXC')
            && Configuration::deleteByName('AEUC_LABEL_COND_PRIVACY')
            && Configuration::deleteByName('AEUC_LABEL_REVOCATION_TOS')
            && Configuration::deleteByName('AEUC_LABEL_REVOCATION_VP')
            && Configuration::deleteByName('AEUC_LABEL_SHIPPING_INC_EXC')
            && Configuration::deleteByName('AEUC_LABEL_COMBINATION_FROM')
            && Configuration::deleteByName('AEUC_LABEL_CUSTOM_CART_TEXT')
            && Configuration::updateValue('PS_ATCP_SHIPWRAP', false)
            && Configuration::deleteByName('AEUC_LABEL_TAX_FOOTER')
            && $this->uninstallSql();
    }

    public function disable(): bool
    {
        return Configuration::updateValue('PS_ATCP_SHIPWRAP', false);
    }

    public function uninstallSql(): bool
    {
        foreach ($this->sql as $name => $v) {
            Db::getInstance()->execute('DROP TABLE IF EXISTS ' . $name);
        }

        return true;
    }

    public function installSql()
    {
        $state = true;

        foreach ($this->sql as $s) {
            $state &= Db::getInstance()->execute($s);
        }

        // Fillin CMS ROLE
        $roles_array = $this->module->getCMSRoles();
        $roles = array_keys($roles_array);
        $cms_role_repository = $this->entityManager->getRepository('CMSRole');

        foreach ($roles as $role) {
            if (!$cms_role_repository->findOneByName($role)) {
                $cms_role = $cms_role_repository->getNewEntity();
                $cms_role->id_cms = 0; // No assoc at this time
                $cms_role->name = $role;
                $state &= (bool) $cms_role->save();
            }
        }

        $emailTemplateFinder = new EmailTemplateFinder($this->emailLister);
        $defaultEmailTemplatePath = $emailTemplateFinder->getDefaultEmailTemplatePath();

        // Fill-in aeuc_mail table
        foreach ($emailTemplateFinder->getAllAvailableEmailTemplates($defaultEmailTemplatePath) as $mail) {
            $new_email = new AeucEmailEntity();
            $new_email->filename = (string) $mail;
            $new_email->display_name = $this->emailLister->getCleanedMailName($mail);
            $new_email->save();

            unset($new_email);
        }

        return $state;
    }

    private function setLegalContentToOrderMails()
    {
        $cms_role_repository = $this->entityManager->getRepository('CMSRole');
        $cms_roles_associated = $cms_role_repository->getCMSRolesAssociated();
        $role_ids_to_set = [];
        $role_id_legal_notice = false;
        $email_ids_to_set = [];
        $account_email_ids_to_set = [];

        foreach ($cms_roles_associated as $role) {
            if (
                $role->name == Roles::CONDITIONS
                || $role->name == Roles::REVOCATION
                || $role->name == Roles::NOTICE
            ) {
                $role_ids_to_set[] = $role->id;
            }

            if ($role->name == Roles::NOTICE) {
                $role_id_legal_notice = $role->id;
            }
        }

        $email_filenames = [
            'backoffice_order',
            'credit_slip',
            'order_canceled',
            'order_changed',
            'order_conf',
            'order_customer_comment',
            'order_merchant_comment',
            'order_return_state',
            'payment',
            'refund',
        ];

        foreach (AeucEmailEntity::getAll() as $email) {
            if (in_array($email['filename'], $email_filenames)) {
                $email_ids_to_set[] = $email['id_mail'];
            }
        }

        $account_newsletter_mail_filenames = [
            'account',
            'newsletter',
            'password',
            'password_query',
        ];

        foreach (AeucEmailEntity::getAll() as $email) {
            if (in_array($email['filename'], $account_newsletter_mail_filenames)) {
                $account_email_ids_to_set[] = $email['id_mail'];
            }
        }

        AeucCMSRoleEmailEntity::truncate();

        foreach ($role_ids_to_set as $role_id) {
            foreach ($email_ids_to_set as $email_id) {
                $assoc_obj = new AeucCMSRoleEmailEntity();
                $assoc_obj->id_mail = (int) $email_id;
                $assoc_obj->id_cms_role = (int) $role_id;
                $assoc_obj->save();
            }
        }

        if ($role_id_legal_notice) {
            foreach ($account_email_ids_to_set as $email_id) {
                $assoc_obj = new AeucCMSRoleEmailEntity();
                $assoc_obj->id_mail = (int) $email_id;
                $assoc_obj->id_cms_role = (int) $role_id_legal_notice;
                $assoc_obj->save();
            }
        }

        return true;
    }
}
