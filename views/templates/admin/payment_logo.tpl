{**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 *}

 <div id="gc-payment-logo">
    <div class="form-group">
        <div class="col-md-6">
            <img src="{$logo_url}" class="img img-thumbnail" style="max-width: 100px; max-height: 100px;"/>
        </div>
    </div>
    {if $is_uploaded_logo}
        <div class="form-group">
            <div class="col-md-12">
                <a href="{$removeLogoUrl}" class="btn btn-danger" onclick="return confirm('{l s='Are you sure want delete the logo?' d='Modules.Legalcompliance.Admin'}');">{l s='Delete' d='Admin.Actions'}</a>
                <div class="help-block">{l s='If delete an uploaded logo, it will be replaced by default.' d='Modules.Legalcompliance.Admin'}</div>
            </div>
        </div>
    {/if}
    <input type="file" name="payment_logo" class="form-control" accept=".png,.jpg"/>
    <div class="help-block">{l s='Upload custom payment logo. The logo will appear on the checkout page' d='Modules.Legalcompliance.Admin'}</div>
</div>
