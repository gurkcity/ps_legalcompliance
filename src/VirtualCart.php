<?php

namespace PSLegalcompliance;

use Cart;

class VirtualCart
{
    public static function hasCartVirtualProduct(Cart $cart): bool
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
