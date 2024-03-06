{**
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author 	PrestaShop SA <contact@prestashop.com>
 *  @copyright  2007-2016 PrestaShop SA
 *  @license    http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 *  International Registered Trademark & Property of PrestaShop SA
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