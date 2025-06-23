{**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 *}

<div id="ps_legalcompliance_cms_content">
    <table style="width: 100%; margin-top:10px; table-layout: fixed;">
        <tbody>
            {foreach from=$cms_contents item=content}
            <tr>
                <td style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; direction: ltr;  padding: 0 50px 20px; text-align: left; font-size: 13px;">
                    {$content nofilter}
                </td>
            </tr>
            {/foreach}
        </tbody>
    </table>
</div>

{if trim($legal_mail_footer) !== ''}
    <div id="ps_legalcompliance_extra_html_footer">
        <table style="width:100%;margin-top:10px">
            <tbody>
                <tr>
                    <td style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; direction: ltr;  padding: 0 50px 20px; text-align: left;">
                        {$legal_mail_footer nofilter}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
{/if}