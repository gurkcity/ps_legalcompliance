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
use PrestaShop\PrestaShop\Adapter\Shop\Context;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;

class ConfigMaintenance implements MaintenanceInterface
{
    const VALUE_STRIP_LENGTH = 200;

    private $config = [];
    private $module;
    private $configurationAdapter;
    private $shopContext;
    private $languages = [];
    private $languageMapping = [];

    public function __construct(
        \PS_Legalcompliance $module,
        ConfigurationAdapter $configurationAdapter,
        Context $shopContext,
        array $languages
    ) {
        $this->module = $module;
        $this->configurationAdapter = $configurationAdapter;
        $this->shopContext = $shopContext;
        $this->languages = $languages;

        $this->config = $this->module->getSettings()->getConfig();

        $this->languageMapping = array_column($this->languages, 'iso_code', 'id_lang');
    }

    public function get(): array
    {
        $configMaintenance = [];

        foreach ($this->config as $config) {
            $configName = $config->getName();

            $configMaintenance[$configName] = [
                'name' => $this->configurationAdapter->getName($configName, $config->usePrefix()),
                'value' => $this->getConfigurationValue($config),
                'valid' => $this->isValid($config),
            ];
        }

        ksort($configMaintenance);

        return $configMaintenance;
    }

    public function reset(): bool
    {
        $registered = true;

        $shopConstraint = $this->getAllShopsConstraint();

        foreach ($this->config as $config) {
            if ($this->isValid($config)) {
                continue;
            }

            $registered = $this->configurationAdapter->set(
                $config->getName(),
                $config->getValue(),
                $config->isHtml(),
                $shopConstraint,
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
        $shopConstraint = $this->getAllShopsConstraint();

        return $this->configurationAdapter->has(
            $config->getName(),
            $shopConstraint,
            $config->usePrefix()
        );
    }

    public function getConfigurationValue(Config $config)
    {
        $shopConstraint = $this->getStrictShopConstraint();

        $hasKey = $this->configurationAdapter->has(
            $config->getName(),
            $shopConstraint,
            $config->usePrefix()
        );

        if (!$hasKey) {
            return null;
        }

        $value = $this->configurationAdapter->get(
            $config->getName(),
            null,
            $shopConstraint,
            $config->usePrefix()
        );

        $this->valueKeyIdToIso($value);

        return $value;
    }

    protected function getAllShopsConstraint(): ShopConstraint
    {
        return ShopConstraint::allShops();
    }

    protected function getStrictShopConstraint(): ShopConstraint
    {
        return $this->shopContext->getShopConstraint(true);
    }

    protected function valueKeyIdToIso(&$array)
    {
        if (is_array($array)) {
            foreach ($array as $idLang => $val) {
                if (!isset($this->languageMapping[$idLang])) {
                    continue;
                }

                $array[$this->languageMapping[$idLang]] = $val;
                unset($array[$idLang]);
            }
        }
    }
}
