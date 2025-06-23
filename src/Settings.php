<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance;

use Doctrine\DBAL\Schema\Schema;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Module\AbstractSettings;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\Config;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\Hook;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\Sql;
use PrestaShop\PrestaShop\Adapter\ServiceLocator;
use PrestaShop\PrestaShop\Core\Email\EmailLister;
use PrestaShop\PrestaShop\Core\Foundation\Database\EntityManager;

class Settings extends AbstractSettings
{
    public function config(): array
    {
        return [
            new Config('AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL', 0, false, false, true, false),
            new Config('AEUC_LABEL_DELIVERY_ADDITIONAL', '', false, false, true, false),
            new Config('AEUC_LABEL_SPECIFIC_PRICE', false, false, false, true, false),
            new Config('AEUC_LABEL_TAX_INC_EXC', true, false, false, true, false),
            new Config('AEUC_LABEL_UNIT_PRICE', true, false, false, true, false),
            new Config('AEUC_LABEL_SHIPPING_INC_EXC', false, false, false, true, false),
            new Config('AEUC_LABEL_COND_PRIVACY', true, false, false, true, false),
            new Config('AEUC_LABEL_REVOCATION_TOS', false, false, false, true, false),
            new Config('AEUC_LABEL_COMBINATION_FROM', true, false, false, true, false),
            new Config('AEUC_LABEL_CUSTOM_CART_TEXT', '', false, false, true, false),
            new Config('AEUC_LABEL_TAX_FOOTER', true, false, false, true, false),
            new Config('AEUC_VP_ACTIVE', false, false, false, true, false),
            new Config('AEUC_VP_CMS_ID', 0, false, false, true, false),
            new Config('AEUC_VP_LABEL_TEXT', '', false, false, true, false),
            new Config('AEUC_LABEL_REVOCATION_VP', true, false, false, true, false),
            new Config('LEGAL_MAIL_FOOTER', '', false, false, true, false),
            new Config('AEUC_LINKBLOCK_FOOTER', 1, false, false, true, false),
            new Config('PS_TAX_DISPLAY', true, false, false, true, false),
            new Config('PS_ATCP_SHIPWRAP', true, false, false, true, false),
            new Config('PS_FINAL_SUMMARY_ENABLED', true, false, false, true, false),
            new Config('PS_DISALLOW_HISTORY_REORDERING', false, false, false, true, false),
        ];
    }

    public function controllers(): array
    {
        return [];
    }

    public function cron(): array
    {
        return [];
    }

    public function hooks(): array
    {
        return [
            new Hook('displayHeader'),
            new Hook('displayProductPriceBlock'),
            new Hook('displayCheckoutSubtotalDetails'),
            new Hook('displayFooter'),
            new Hook('displayFooterAfter'),
            new Hook('actionEmailSendBefore'),
            new Hook('actionEmailAddAfterContent'),
            new Hook('displayCMSPrintButton'),
            new Hook('displayCMSDisputeInformation'),
            new Hook('termsAndConditions'),
            new Hook('displayCheckoutSummaryTop'),
            new Hook('sendMailAlterTemplateVars'),
            new Hook('displayReassurance'),
            new Hook('actionAdminControllerSetMedia'),
        ];
    }

    public function orderStates(): array
    {
        return [];
    }

    public function sql(): Sql
    {
        $schema = new Schema();

        $table = $schema->createTable($this->dbPrefix . 'aeuc_cmsrole_email');
        $table->addColumn('id', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('id_cms_role', 'integer', ['unsigned' => true]);
        $table->addColumn('id_mail', 'integer', ['unsigned' => true]);
        $table->setPrimaryKey(['id']);

        $table = $schema->createTable($this->dbPrefix . 'aeuc_email');
        $table->addColumn('id_mail', 'integer', ['unsigned' => true, 'autoincrement' => true]);
        $table->addColumn('filename', 'string', ['length' => 64]);
        $table->addColumn('display_name', 'string', ['length' => 64]);
        $table->setPrimaryKey(['id_mail']);

        return new Sql($schema);
    }

    public function tabs(): array
    {
        return [];
    }

    public function translations(): array
    {
        return [
            // Locale
            'de-DE' => [
                // Original, Translation, Domain
                ['Awaiting payment: Legalcompliance', 'Warten auf Zahlungsgeingang: Modul Template', 'ModulesPslegalcomplianceShop'],
                ['Order placed', 'Bestellung eingegangen', 'ModulesPslegalcomplianceShop'],
                ['Legalcompliance Module', 'Legalcompliance Modul', 'ModulesPslegalcomplianceShop'],
                ['Configuration', 'Konfiguration', 'ModulesPslegalcomplianceShop'],
                ['Cron', 'Cron', 'ModulesPslegalcomplianceShop'],
                ['Logs', 'Logs', 'ModulesPslegalcomplianceShop'],
                ['Maintenance', 'Wartung', 'ModulesPslegalcomplianceShop'],
                ['License', 'Lizens', 'ModulesPslegalcomplianceShop'],
            ],
        ];
    }

    public function fixtures(): callable
    {
        return function () {
            $state = true;

            /** @var EntityManager $entityManager */
            $entityManager = ServiceLocator::get(EntityManager::class);

            /** @var EmailLister $emailLister */
            $emailLister = ServiceLocator::get(EmailLister::class);

            $roles = [
                Roles::NOTICE,
                Roles::CONDITIONS,
                Roles::REVOCATION,
                Roles::REVOCATION_FORM,
                Roles::PRIVACY,
                Roles::ENVIRONMENTAL,
                Roles::SHIP_PAY,
            ];

            $cms_role_repository = $entityManager->getRepository('CMSRole');

            foreach ($roles as $role) {
                if (!$cms_role_repository->findOneByName($role)) {
                    $cms_role = $cms_role_repository->getNewEntity();
                    $cms_role->id_cms = 0; // No assoc at this time
                    $cms_role->name = $role;
                    $state &= (bool) $cms_role->save();
                }
            }

            $emailTemplateFinder = new EmailTemplateFinder($emailLister);
            $defaultEmailTemplatePath = $emailTemplateFinder->getDefaultEmailTemplatePath();

            // Fill-in aeuc_mail table
            foreach ($emailTemplateFinder->getAllAvailableEmailTemplates($defaultEmailTemplatePath) as $mail) {
                $new_email = new \AeucEmailEntity();
                $new_email->filename = (string) $mail;
                $new_email->display_name = $emailLister->getCleanedMailName($mail);
                $new_email->save();

                unset($new_email);
            }

            $cms_role_repository = $entityManager->getRepository('CMSRole');
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

            foreach (\AeucEmailEntity::getAll() as $email) {
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

            foreach (\AeucEmailEntity::getAll() as $email) {
                if (in_array($email['filename'], $account_newsletter_mail_filenames)) {
                    $account_email_ids_to_set[] = $email['id_mail'];
                }
            }

            \AeucCMSRoleEmailEntity::truncate();

            foreach ($role_ids_to_set as $role_id) {
                foreach ($email_ids_to_set as $email_id) {
                    $assoc_obj = new \AeucCMSRoleEmailEntity();
                    $assoc_obj->id_mail = (int) $email_id;
                    $assoc_obj->id_cms_role = (int) $role_id;
                    $assoc_obj->save();
                }
            }

            if ($role_id_legal_notice) {
                foreach ($account_email_ids_to_set as $email_id) {
                    $assoc_obj = new \AeucCMSRoleEmailEntity();
                    $assoc_obj->id_mail = (int) $email_id;
                    $assoc_obj->id_cms_role = (int) $role_id_legal_notice;
                    $assoc_obj->save();
                }
            }

            return true;
        };
    }
}
