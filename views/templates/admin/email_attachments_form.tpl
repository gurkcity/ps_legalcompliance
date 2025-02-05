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

<form id="emailAttachementsManager" class="defaultForm form-horizontal" action="{$form_action}" method="POST" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="AEUC_emailAttachmentsManager" value="1">
    <div class="panel">
        <div class="panel-heading">
            <i class="icon-envelope"></i>
            {l s='Email content inclusion' d='Modules.Legalcompliance.Admin'}
        </div>
        <div class="row">
            <div class="col-sm-6">
                <p>
                    {l s='This section allows you to include information from the "Legal Content Management" section above at the bottom of your shop\'s emails.' d='Modules.Legalcompliance.Admin'}
                </p>
                <p>
                    {l s='For each type of email, you can define which content you would like to include.' d='Modules.Legalcompliance.Admin'}
                </p>
            </div>
            <div class="col-sm-6">
                {if $emailTemplatesMissing}
                  <div class="new_emails alert alert-info">
                    <p>{l s='There are %count% new email templates found:' sprintf=['%count%' => count($emailTemplatesMissing)] d='Modules.Pslegalcompliance.Admin'}</p>
                    <ul>
                        {foreach from=$emailTemplatesMissing item=emailTemplate}
                            <li>{$emailTemplate}</li>
                        {/foreach}
                    </ul>
                    <a href="{$check_new_templates_link}" class="btn btn-primary">{l s='Insert new email templates' d='Modules.Legalcompliance.Admin'}</a>
                  </div>
                {/if}
            </div>
        </div>
        <br/>
        <div class="form-wrapper">
            <table class="table accesses">
                <thead>
                <tr>
                    <th>
                        <span class="title_box">
                            <input id="selectall_attach" type="checkbox"/>
                            {l s='Email templates' d='Modules.Legalcompliance.Admin'}
                        </span>

                    </th>
                    {foreach from=$legal_options item=option}
                        <th class="center fixed-width-xs">
                            <span class="title_box">
                                 <input id="selectall_opt_{$option.id|intval}" type="checkbox"/>
                                {$option.name|escape:'htmlall'}
                            </span>
                        </th>
                    {/foreach}
                </tr>
                </thead>
                <tbody>
                {foreach from=$mails_available item=mail}
                    <tr>
                        <td><input id="mail_{$mail.id_mail|intval}" class="select-all-for-mail" type="checkbox"/></th>&nbsp;{$mail.display_name|escape:'htmlall'}</td>
                        {foreach from=$legal_options item=option}
                            <td class="center">
                                <input name="attach_{$mail.id_mail|intval}_{$option.id|intval}" id="attach_{$mail.id_mail|intval}_{$option.id|intval}" type="checkbox"
                                {if in_array($mail.id_mail, $option.list_id_mail_assoc)}
                                    checked="true"
                                {/if}
                                /></th>
                            </td>
                        {/foreach}
                    </tr>
                {/foreach}
                <tr>
                    <td class="td-primary-top"><input id="mail_pdf" class="select-all-for-mail" type="checkbox"/>&nbsp;{l s='PDF Attachment'}</td>
                    {foreach from=$legal_options item=option}
                        <td class="center td-primary-top">
                            <input name="pdf_attachment[{$option.id|intval}]" id="attach_pdf_{$option.id|intval}" type="checkbox" {if in_array($option.id, $pdf_attachment)}checked="checked"{/if}/>
                        </td>
                    {/foreach}
                </tr>
                </tbody>
            </table>
        </div>

        <div class="panel-footer">
            <button type="submit" class="btn btn-default pull-right">
                <i class="process-icon-save"></i>  {l s='Save' d='Admin.Actions'}
            </button>

        </div>
    </div>
</form>


