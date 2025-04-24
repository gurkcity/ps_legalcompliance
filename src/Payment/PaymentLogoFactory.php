<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Payment;

class PaymentLogoFactory
{
    private $module;

    public function __construct(\PS_Legalcompliance $module)
    {
        $this->module = $module;
    }

    public function getPaymentLogo(): PaymentLogo
    {
        $config = $this->module->getConfig();
        $modulePath = $this->module->getLocalPath();
        $modulePathUri = $this->module->getPathUri();

        return new PaymentLogo(
            $modulePath,
            $modulePathUri,
            (string) $config->get('PAYMENT_LOGO')
        );
    }
}
