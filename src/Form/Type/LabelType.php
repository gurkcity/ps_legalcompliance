<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\Type;

use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\FormBuilderInterface;

class LabelType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('AEUC_LABEL_DISPLAY_DELIVERY_ADDITIONAL', SwitchType::class, [
                'label' => $this->trans('Additional information about delivery time', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('If you specified a delivery time...', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
            ->add('AEUC_LABEL_DELIVERY_ADDITIONAL', TranslatableType::class, [
                'label' => $this->trans('Hint', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('If you specified a delivery time, this additional information is displayed in the footer of product pages with a link to the "Shipping & Payment" Page. Leave the field empty to disable.', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
            ->add('AEUC_LABEL_SPECIFIC_PRICE', SwitchType::class, [
                'label' => $this->trans('\'Our previous price\' label', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('When a product is on sale, displays a \'Our previous price\' label before the original price crossed out, next to the price on the product page.', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
            ->add('AEUC_LABEL_TAX_INC_EXC', SwitchType::class, [
                'label' => $this->trans('Tax \'inc./excl.\' label', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('Displays whether the tax is included on the product page (\'Tax incl./excl.\' label) and adds a short mention in the footer of other pages.', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
            ->add('AEUC_LABEL_UNIT_PRICE', SwitchType::class, [
                'label' => $this->trans('Price per unit label', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('If available, displays the price per unit everywhere the product price is listed.', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
            ->add('AEUC_LABEL_SHIPPING_INC_EXC', SwitchType::class, [
                'label' => $this->trans('\'Shipping fees excl.\' label', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('Displays a label next to the product price (\'Shipping excluded\') and adds a short mention in the footer of other pages.', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
            ->add('AEUC_LABEL_COND_PRIVACY', SwitchType::class, [
                'label' => $this->trans('Show Conditions Checkbox', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('Shows a checkbox to confirm conditions privacy and revocation (default: Yes)', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
            ->add('AEUC_LABEL_REVOCATION_TOS', SwitchType::class, [
                'label' => $this->trans('Revocation Terms within ToS', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('Includes content from the Revocation Terms page within the Terms of Services (ToS).', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
            ->add('AEUC_LABEL_COMBINATION_FROM', SwitchType::class, [
                'label' => $this->trans('\'From\' price label (when combinations)', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('Displays a \'From\' label before the price on products with combinations.', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
            ->add('AEUC_LABEL_CUSTOM_CART_TEXT', TranslatableType::class, [
                'label' => $this->trans('Custom text in shopping cart page', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('This text will be displayed on the shopping cart page. Leave empty to disable.', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
            ->add('AEUC_LABEL_TAX_FOOTER', SwitchType::class, [
                'label' => $this->trans('Display tax in footer', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('Displays the tax informations in the footer.', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
        ;
    }
}
