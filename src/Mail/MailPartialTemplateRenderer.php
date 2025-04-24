<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Mail;

use PrestaShop\PrestaShop\Core\Language\LanguageInterface;

class MailPartialTemplateRenderer
{
    private $moduleName;
    private $smarty;

    public function __construct(string $moduleName, \Smarty $smarty)
    {
        $this->moduleName = $moduleName;
        $this->smarty = $smarty;
    }

    public function render($partialTemplateName, LanguageInterface $language, array $variables = [], $cleanComments = false)
    {
        $potentialPaths = [
            _PS_THEME_DIR_ . 'modules' . DIRECTORY_SEPARATOR . $this->moduleName . DIRECTORY_SEPARATOR . 'mails' . DIRECTORY_SEPARATOR . $language->getIsoCode() . DIRECTORY_SEPARATOR . $partialTemplateName,
            _PS_MODULE_DIR_ . $this->moduleName . DIRECTORY_SEPARATOR . 'mails' . DIRECTORY_SEPARATOR . $language->getIsoCode() . DIRECTORY_SEPARATOR . $partialTemplateName,
            _PS_THEME_DIR_ . 'modules' . DIRECTORY_SEPARATOR . $this->moduleName . DIRECTORY_SEPARATOR . 'mails' . DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR . $partialTemplateName,
            _PS_MODULE_DIR_ . $this->moduleName . DIRECTORY_SEPARATOR . 'mails' . DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR . $partialTemplateName,
            _PS_MAIL_DIR_ . 'modules' . DIRECTORY_SEPARATOR . $this->moduleName . DIRECTORY_SEPARATOR . 'mails' . DIRECTORY_SEPARATOR . '_partials' . DIRECTORY_SEPARATOR . $partialTemplateName,

            _PS_THEME_DIR_ . 'mails' . DIRECTORY_SEPARATOR . $language->getIsoCode() . DIRECTORY_SEPARATOR . $partialTemplateName,
            _PS_MAIL_DIR_ . $language->getIsoCode() . DIRECTORY_SEPARATOR . $partialTemplateName,
            _PS_THEME_DIR_ . 'mails' . DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR . $partialTemplateName,
            _PS_MAIL_DIR_ . 'en' . DIRECTORY_SEPARATOR . $partialTemplateName,
            _PS_MAIL_DIR_ . '_partials' . DIRECTORY_SEPARATOR . $partialTemplateName,
        ];

        foreach ($potentialPaths as $path) {
            if (\Tools::file_exists_cache($path)) {
                $this->smarty->assign($variables);

                $content = $this->smarty->fetch($path);

                if ($cleanComments) {
                    $content = preg_replace('/\s?<!--.*?-->\s?/s', '', $content);
                }

                return $content;
            }
        }

        return '';
    }
}
