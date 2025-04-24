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

use Context;
use Shop;

trait ObjectAdminControllerTrait
{
    protected function associateWithShops(\ObjectModel $objectModel, array $shopAssociation)
    {
        if (empty($shopAssociation) || !\Shop::isFeatureActive()) {
            return;
        }

        $tableName = (string) $objectModel::$definition['table'];
        $primaryKeyName = (string) $objectModel::$definition['primary'];
        $primaryKeyValue = (int) $objectModel->id;

        if (!\Shop::isTableAssociated($tableName)) {
            return;
        }

        // Get list of shop id we want to exclude from asso deletion
        $excludeIds = $shopAssociation;
        foreach (\Db::getInstance()->executeS('SELECT id_shop FROM ' . _DB_PREFIX_ . 'shop') as $row) {
            if (!\Context::getContext()->employee->hasAuthOnShop($row['id_shop'])) {
                $excludeIds[] = $row['id_shop'];
            }
        }

        $excludeShopsCondtion =
            ' AND id_shop NOT IN (' . implode(', ', array_map('intval', $excludeIds)) . ')';

        \Db::getInstance()->delete(
            $tableName . '_shop',
            '`' . $primaryKeyName . '` = ' . $primaryKeyValue . $excludeShopsCondtion
        );

        $insert = [];
        foreach ($shopAssociation as $shopId) {
            // Check if context employee has access to the shop before inserting shop association.
            if (\Context::getContext()->employee->hasAuthOnShop($shopId)) {
                $insert[] = [
                    $primaryKeyName => $primaryKeyValue,
                    'id_shop' => (int) $shopId,
                ];
            }
        }

        \Db::getInstance()->insert(
            $tableName . '_shop',
            $insert,
            false,
            true,
            \Db::INSERT_IGNORE
        );
    }
}
