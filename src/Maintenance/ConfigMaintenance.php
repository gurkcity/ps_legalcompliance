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

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Module\ConfigurationAdapter;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\Config;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\SettingsInterface;

class ConfigMaintenance implements MaintenanceInterface
{
    const VALUE_STRIP_LENGTH = 200;

    private $config = [];
    private $module;
    private $configurationAdapter;

    public function __construct(\PS_Legalcompliance $module, ConfigurationAdapter $configurationAdapter)
    {
        $this->module = $module;
        $this->configurationAdapter = $configurationAdapter;

        $this->config = $this->module->getSettings()->getConfig();
    }

    public function get(): array
    {
        $configMaintenance = [];

        foreach ($this->config as $config) {
            $configName = $config->getName();

            $configMaintenance[$configName] = [
                'name' => $this->configurationAdapter->getName($configName, $config->usePrefix()),
                'value' => $this->configurationAdapter->get($configName, null, null, $config->usePrefix()),
                'valid' => $this->isValid($config),
            ];
        }

        ksort($configMaintenance);

        return $configMaintenance;
    }

    public function reset(): bool
    {
        $registered = true;

        foreach ($this->config as $config) {
            if ($this->isValid($config)) {
                continue;
            }

            $registered = $this->configurationAdapter->set(
                $config->getName(),
                $config->getValue(),
                $config->isHtml(),
                null,
                $config->usePrefix()
            ) && $registered;
        }

        return $registered;
    }

    public function remove(): bool
    {
        // TODO: Implement method
        return true;
    }

    public function isValid(SettingsInterface $config): bool
    {
        /** @var Config $config */
        $configRealName = $this->configurationAdapter->getName($config->getName(), $config->usePrefix());

        return (bool) \Configuration::getIdByName($configRealName);
    }
}
