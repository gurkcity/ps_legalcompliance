{**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 *}

{if $isAssociated}
<h4>
	{l s='Information regarding online dispute resolution pursuant to Art. 14 Para. 1 of the ODR (Online Dispute Resolution Regulation):' d='Modules.Legalcompliance.Shop'}
</h4>

<p>
	{l s='The European Commission gives consumers the opportunity to resolve online disputes pursuant to Art. 14 Para. 1 of the ODR on one of their platforms. The platform ([1]http://ec.europa.eu/consumers/odr[/1]) serves as a site where consumers can try to reach out-of-court settlements of disputes arising from online purchases and contracts for services.' sprintf=['[1]' => '<a href="http://ec.europa.eu/consumers/odr" target="_blank" rel="nofollow">', '[/1]' => '</a>'] d='Modules.Legalcompliance.Shop'}
</p>
{/if}
