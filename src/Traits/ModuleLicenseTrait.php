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

trait ModuleLicenseTrait
{
    /**
     * Note: PHP trait constants are only available from version 8.2, so a property is used here instead of:
     * const OSM_LICENSE_URL = 'https://www.onlineshop-module.de/lizenz';
     */
    protected $OSM_LICENSE_URL = 'https://www.onlineshop-module.de/lizenz';

    public function isLicensed(): bool
    {
        $config = $this->getConfig();

        return $config->get('LICENSE') && $config->get('PRIVACY');
    }

    public function getLicenseKey(): string
    {
        return $this->getConfig()->get('LICENSE');
    }

    public function registerLicense(string $license)
    {
        $paramsString = http_build_query([
            'm' => $this->name,
            'v' => $this->version,
            'l' => $license,
            'd' => \Context::getContext()->shop->domain,
        ]);

        \Tools::file_get_contents($this->OSM_LICENSE_URL . '?' . $paramsString);

        $logger = $this->getLogger()->getInstanceOrCreate('license');
        $logger->info(sprintf('Send license to host %s', $license));
    }
}
