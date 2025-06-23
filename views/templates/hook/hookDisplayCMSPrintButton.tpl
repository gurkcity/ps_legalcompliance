{**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 *}

{if $showButton}
  {if $directPrint}
    <input type="submit" name="printCMSPage" value="{l s='Print this page' d='Modules.Legalcompliance.Shop'}" class="btn btn-secondary" onclick="window.print()" />
  {else}
    <a href="{$print_link}" class="btn btn-secondary" target="_blank">{l s='Print this page' d='Modules.Legalcompliance.Shop'}</a>
  {/if}
{/if}
