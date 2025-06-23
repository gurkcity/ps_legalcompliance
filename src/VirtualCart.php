<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance;

class VirtualCart
{
    public static function hasCartVirtualProduct(\Cart $cart): bool
    {
        $products = $cart->getProducts();

        if (!count($products)) {
            return false;
        }

        foreach ($products as $product) {
            if ($product['is_virtual']) {
                return true;
            }
        }

        return false;
    }
}
