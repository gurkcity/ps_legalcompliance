{**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 *}

 <div class="euAboutUsCMS col-md-2">
 	<h3 class="h3">{l s='Information' d='Modules.Legalcompliance.Shop'}</h3>
 	<ul>
 		{foreach from=$cms_links item=cms_link}
 			<li>
 				<a href="{$cms_link.link}" class="cms-page-link" title="{$cms_link.description|default:''}" id="{$cms_link.id}"> {$cms_link.title} </a>
 			</li>
 		{/foreach}
 	</ul>
 </div>
