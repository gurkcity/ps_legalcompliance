{**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 *}

{foreach from=$cms_contents item=content}
    <tr>
        <td style="width:20px;padding:7px 0">&nbsp;</td>
        <td style="padding:7px 0">
            {$content nofilter}
        </td>
        <td style="width:20px;padding:7px 0">&nbsp;</td>
    </tr>
{/foreach}

{if trim($legal_mail_footer) !== ''}
    <tr>
        <td style="width:20px;padding:7px 0">&nbsp;</td>
        <td style="padding:7px 0">
            {$legal_mail_footer nofilter}
        </td>
        <td style="width:20px;padding:7px 0">&nbsp;</td>
    </tr>
{/if}