{**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 *}

{* "Unit Price" Price Hook templating *}
{if isset($smartyVars.unit_price)}
    <div class="aeuc_unit_price_label">
        {$smartyVars.unit_price}
    </div>
{/if}
