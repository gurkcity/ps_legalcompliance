{**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 *}

{extends file='page.tpl'}

{block name="page_content"}

<form action="{$link->getModuleLink($module_name, 'payment', [], true)}" method="post">
    <h3>{l s='Payment with Legalcompliance' d='Modules.Legalcompliance.Shop'}</h3>
    <p>
        <b>
            {l s='You have chosen to pay by Legalcompliance.' d='Modules.Legalcompliance.Shop'}
        </b>
    </p>
    <p>
        {l s='The total amount of your order is' d='Modules.Legalcompliance.Shop'} <u>{$total}</u><br>
        {l s='Please confirm your order by clicking "Place my order"' d='Modules.Legalcompliance.Shop'}
    </p>

    <input type="hidden" name="submitPayment" value="1" />

    <div class="clearfix">
        <a class="btn btn-default float-left" href="{$link->getPageLink('order', true, NULL, "step=3")}">{l s='Other payment methods' d='Modules.Legalcompliance.Shop'}</a>
        <button class="btn btn-primary float-right" type="submit">{l s='Place my order' d='Modules.Legalcompliance.Shop'}</button>
    </div>
</form>

{/block}
