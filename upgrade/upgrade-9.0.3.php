<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Maintenance\Maintenance;

if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param PS_Legalcompliance $module
 *
 * @return bool
 */
function upgrade_module_9_0_3($module)
{
    try {
        $result = true;

        if (!Module::isEnabled($module->name)) {
            return false;
        }

        /**
         * @var Maintenance $maintenance
         */
        $maintenance = $module->get('onlineshopmodule.module.legalcompliance.maintenance');

        $result = $maintenance->resetHooks();

        return (bool) $result;
    } catch (Exception $e) {
        /* @phpstan-ignore property.notFound */
        $module->getLogger()->upgrade->error($e->getMessage());

        return false;
    }
}
