<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\DataProvider;

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\AbstractFormDataProvider;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

class PaymentFormDataProvider extends AbstractFormDataProvider implements FormDataProviderInterface
{
    public function getData()
    {
        return [
            'os_neworder' => $this->configurationAdapter->get('OS_NEWORDER'),
            'awaiting_payment' => $this->configurationAdapter->get('AWAITING_PAYMENT'),
            'os' => $this->configurationAdapter->get('OS'),
            'show_payment_logo' => $this->configurationAdapter->get('SHOW_PAYMENT_LOGO'),
        ];
    }

    public function setData(array $data)
    {
        $this->updateConfiguration('OS_NEWORDER', 'os_neworder', (int) $data['os_neworder']);
        $this->updateConfiguration('AWAITING_PAYMENT', 'awaiting_payment', (int) $data['awaiting_payment']);
        $this->updateConfiguration('OS', 'os', (int) $data['os']);
        $this->updateConfiguration('SHOW_PAYMENT_LOGO', 'show_payment_logo', (int) $data['show_payment_logo']);

        return [];
    }
}
