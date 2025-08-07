<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Maintenance;

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\SettingsInterface;

interface MaintenanceInterface
{
    public function get(): array;

    public function reset(): bool;

    public function remove(): bool;
}
