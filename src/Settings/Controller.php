<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings;

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Exception\SettingException;

class Controller implements SettingsInterface
{
    private $name = '';

    public function __construct(string $name)
    {
        if (!\Validate::isControllerName($name)) {
            throw new SettingException('Controller name is not valid');
        }

        $this->name = $name;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function __toString()
    {
        return $this->getName();
    }
}
