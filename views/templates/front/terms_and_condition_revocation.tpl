{**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 *}

<script type="text/javascript">
    {* hide checkbox to terms_and_contions *}
    {if isset($checkbox_identifier) && $checkbox_identifier}
    {literal}
        window.addEventListener("load", function () {
            var selector = 'input' + '[name*="' +
                '{/literal}{$checkbox_identifier}{literal}"]';
            var input = jQuery(selector);

            // set checked position
            input
                .prop('checked', true)
                .trigger('change');

            // hide checkbox wrapper
            input.closest('div').hide();

            // rebuild label
            var selector = 'label' + '[for*="' +
                '{/literal}{$checkbox_identifier}{literal}"]';
            jQuery(selector).closest('.condition-label').addClass('p-0');
            jQuery(selector).attr('for', '');
        });
    {/literal}
    {/if}
</script>
