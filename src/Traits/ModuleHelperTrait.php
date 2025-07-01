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

use PrestaShop\PrestaShop\Adapter\SymfonyContainer;

trait ModuleHelperTrait
{
    public function isUsingNewTranslationSystem()
    {
        return true;
    }

    public function smartyAssign(array $params, string $template = ''): string
    {
        if ($params) {
            $this->smarty->assign($params);
        }

        if ($template) {
            return $this->fetch($this->getLocalPath() . $template);
        }

        return '';
    }

    public function getContent()
    {
        if (!\Module::isEnabled($this->name)) {
            return '<div class="alert alert-warning">' . $this->trans('This module is disabled. Please enable it.', [], 'Modules.Legalcompliance.Admin') . '</div>';
        }

        \Tools::redirectAdmin(
            SymfonyContainer::getInstance()->get('router')->generate($this->name . '_configuration')
        );
    }

    public static function isDevMode(): bool
    {
        if (
            !empty($_SERVER['SERVER_NAME'])
            && $_SERVER['SERVER_NAME'] == 'localhost'
        ) {
            return true;
        }

        if (
            !empty($_SERVER['SERVER_ADDR'])
            && (
                $_SERVER['SERVER_ADDR'] == '127.0.0.1'
                || $_SERVER['SERVER_ADDR'] == '::1'
            )
        ) {
            return true;
        }

        return false;
    }

    public function getCookie(): \Cookie
    {
        return new \Cookie('bo_messages', '', time() + \Configuration::get('PS_COOKIE_LIFETIME_BO') * 60);
    }

    public function getGCModuleVersion(): string
    {
        return self::GC_VERSION;
    }

    public function getGCModuleSubversion(): string
    {
        return self::GC_SUBVERSION;
    }
}
