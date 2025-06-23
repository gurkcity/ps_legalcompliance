{**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 *}

{if $delivery_additional_information || $display_tax_information}
<div class="aeuc_footer_info">
	{if $delivery_additional_information}
		* {$delivery_additional_information}
		{if $link_shipping}<a href="{$link_shipping}">{l s='Shipping and payment' d='Modules.Legalcompliance.Shop'}</a>{/if}
	{/if}

	{if $display_tax_information}
		{if $delivery_additional_information}<br/>{/if}
		{if $tax_included}
			{l s='All prices are mentioned tax included' d='Modules.Legalcompliance.Shop'}
		{else}
			{l s='All prices are mentioned tax excluded' d='Modules.Legalcompliance.Shop'}
		{/if}

		{if $show_shipping}
			{l s='and' d='Modules.Legalcompliance.Shop'}
			{if $link_shipping}<a href="{$link_shipping}">{/if}
			{l s='shipping excluded' d='Modules.Legalcompliance.Shop'}
			{if $link_shipping}</a>{/if}
		{/if}
	{/if}
</div>
{/if}
