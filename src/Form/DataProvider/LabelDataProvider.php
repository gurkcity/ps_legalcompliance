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

use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

class LabelDataProvider implements FormDataProviderInterface
{
    private $configuration;
    private $languages;

    public function __construct(Configuration $configuration, array $languages)
    {
        $this->configuration = $configuration;
        $this->languages = $languages;
    }

    public function getData()
    {
        $labelDeliveryAdditional = $this->configuration->get('AEUC_LABEL_DELIVERY_ADDITIONAL');

        if (!is_array($labelDeliveryAdditional)) {
            $tempLabelDeliveryAdditional = [];

            foreach ($this->languages as $lang) {
                $tempLabelDeliveryAdditional[$lang['id_lang']] = $labelDeliveryAdditional;
            }

            $labelDeliveryAdditional = $tempLabelDeliveryAdditional;
        }

        $customCartText = $this->configuration->get('AEUC_LABEL_CUSTOM_CART_TEXT');

        if (!is_array($customCartText)) {
            $tempCustomCartText = [];

            foreach ($this->languages as $lang) {
                $tempCustomCartText[$lang['id_lang']] = $customCartText;
            }

            $customCartText = $tempCustomCartText;
        }

        return [
            'AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL' => (bool) $this->configuration->get('AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL'),
            'AEUC_LABEL_DELIVERY_ADDITIONAL' => $labelDeliveryAdditional,
            'AEUC_LABEL_CUSTOM_CART_TEXT' => $customCartText,
            'AEUC_LABEL_SPECIFIC_PRICE' => (bool) $this->configuration->get('AEUC_LABEL_SPECIFIC_PRICE'),
            'AEUC_LABEL_UNIT_PRICE' => (bool) $this->configuration->get('AEUC_LABEL_UNIT_PRICE'),
            'AEUC_LABEL_COND_PRIVACY' => (bool) $this->configuration->get('AEUC_LABEL_COND_PRIVACY'),
            'AEUC_LABEL_REVOCATION_TOS' => (bool) $this->configuration->get('AEUC_LABEL_REVOCATION_TOS'),
            'AEUC_LABEL_SHIPPING_INC_EXC' => (bool) $this->configuration->get('AEUC_LABEL_SHIPPING_INC_EXC'),
            'AEUC_LABEL_COMBINATION_FROM' => (bool) $this->configuration->get('AEUC_LABEL_COMBINATION_FROM'),
            'AEUC_LABEL_TAX_FOOTER' => (bool) $this->configuration->get('AEUC_LABEL_TAX_FOOTER'),
        ];
    }

    public function setData(array $data)
    {
        $deliveryAdditional = [];
        $customCartText = [];

        foreach ($this->languages as $lang) {
            $deliveryAdditional[(int) $lang['id_lang']] = trim($data['AEUC_LABEL_DELIVERY_ADDITIONAL'][$lang['id_lang']] ?? '');
            $customCartText[(int) $lang['id_lang']] = trim($data['AEUC_LABEL_CUSTOM_CART_TEXT'][$lang['id_lang']] ?? '');
        }

        $this->configuration->set('AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL', (bool) $data['AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL']);
        $this->configuration->set('AEUC_LABEL_DELIVERY_ADDITIONAL', $deliveryAdditional);
        $this->configuration->set('AEUC_LABEL_CUSTOM_CART_TEXT', $customCartText);
        $this->configuration->set('AEUC_LABEL_SPECIFIC_PRICE', (bool) $data['AEUC_LABEL_SPECIFIC_PRICE']);
        $this->configuration->set('AEUC_LABEL_UNIT_PRICE', (bool) $data['AEUC_LABEL_UNIT_PRICE']);
        $this->configuration->set('AEUC_LABEL_COND_PRIVACY', (bool) $data['AEUC_LABEL_COND_PRIVACY']);
        $this->configuration->set('AEUC_LABEL_REVOCATION_TOS', (bool) $data['AEUC_LABEL_REVOCATION_TOS']);
        $this->configuration->set('AEUC_LABEL_SHIPPING_INC_EXC', (bool) $data['AEUC_LABEL_SHIPPING_INC_EXC']);
        $this->configuration->set('AEUC_LABEL_COMBINATION_FROM', (bool) $data['AEUC_LABEL_COMBINATION_FROM']);
        $this->configuration->set('AEUC_LABEL_TAX_FOOTER', (bool) $data['AEUC_LABEL_TAX_FOOTER']);

        return [];
    }
}
