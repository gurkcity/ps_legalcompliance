{**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 *}

{if isset($smartyVars.ship.link_ship_pay)}
    <span class="aeuc_shipping_label">
        <a href="{$smartyVars.ship.link_ship_pay}" class="iframe">
            {$smartyVars.ship.ship_str_i18n|escape:'htmlall'}
        </a>
    </span>
{/if}
