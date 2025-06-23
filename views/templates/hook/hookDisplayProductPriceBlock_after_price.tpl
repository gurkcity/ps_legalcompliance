{**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 *}

{if isset($smartyVars.after_price.delivery_str_i18n)}
    <span class="aeuc_delivery_label">
        {$smartyVars.after_price.delivery_str_i18n|escape:'htmlall'}
    </span>
{/if}
