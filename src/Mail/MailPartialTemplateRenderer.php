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
            implode(DIRECTORY_SEPARATOR, [_PS_THEME_DIR_ . 'modules', $this->moduleName, 'mails', $language->getIsoCode(), $partialTemplateName]),
            implode(DIRECTORY_SEPARATOR, [_PS_MODULE_DIR_ . $this->moduleName, 'mails', $language->getIsoCode(), $partialTemplateName]),
            implode(DIRECTORY_SEPARATOR, [_PS_THEME_DIR_ . 'modules', $this->moduleName, 'mails', 'en', $partialTemplateName]),
            implode(DIRECTORY_SEPARATOR, [_PS_MODULE_DIR_ . $this->moduleName, 'mails', 'en', $partialTemplateName]),
            implode(DIRECTORY_SEPARATOR, [_PS_MODULE_DIR_ . $this->moduleName, 'mails', '_partials', $partialTemplateName]),

            implode(DIRECTORY_SEPARATOR, [_PS_THEME_DIR_ . 'mails', $language->getIsoCode(), $partialTemplateName]),
            implode(DIRECTORY_SEPARATOR, [_PS_MAIL_DIR_ . $language->getIsoCode(), $partialTemplateName]),
            implode(DIRECTORY_SEPARATOR, [_PS_THEME_DIR_ . 'mails', 'en', $partialTemplateName]),
            implode(DIRECTORY_SEPARATOR, [_PS_MAIL_DIR_ . 'en', $partialTemplateName]),
            implode(DIRECTORY_SEPARATOR, [_PS_MAIL_DIR_ . '_partials', $partialTemplateName]),
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
