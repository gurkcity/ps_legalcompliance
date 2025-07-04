<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

/**
 * @param PS_Legalcompliance $module
 *
 * @return bool
 */
function upgrade_module_9_0_1($module)
{
    try {
        $result = true;

        if (!Module::isEnabled($module->name)) {
            return false;
        }

        $module->unregisterHook('displayCartTotalPriceLabel') && $result;
        $module->unregisterHook('displayOverrideTemplate') && $result;

        return (bool) $result;
    } catch (Exception $e) {
        /* @phpstan-ignore property.notFound */
        $module->getLogger()->upgrade->error($e->getMessage());

        return false;
    }
}
