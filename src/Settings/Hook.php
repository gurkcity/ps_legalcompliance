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

class Hook implements SettingsInterface
{
    private $name = '';
    private $alternatives = [];

    public function __construct(string $name, array $alternatives = [])
    {
        if (!\Validate::isHookName($name)) {
            throw new SettingException('Hook name is not valid');
        }

        $this->name = $name;
        $this->alternatives = $alternatives;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getAlternatives(): array
    {
        return $this->alternatives;
    }

    public function __toString()
    {
        return $this->getName();
    }

    public function hasAlternatives(): bool
    {
        return !empty($this->alternatives);
    }

    public function isInAlternatives(string $hookName): bool
    {
        if (!$this->hasAlternatives()) {
            return false;
        }

        return in_array($hookName, $this->alternatives);
    }
}
