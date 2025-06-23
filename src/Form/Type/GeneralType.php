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

use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\CleanHtml;
use PrestaShopBundle\Form\Admin\Type\FormattedTextareaType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslateType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\FormBuilderInterface;

class GeneralType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('AEUC_FEAT_REORDER', SwitchType::class, [
                'label' => $this->trans('Enable \'Reordering\' feature', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('Make sure you comply with your local legislation before enabling: it can be considered as unsolicited goods.', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
            ->add('PS_ATCP_SHIPWRAP', SwitchType::class, [
                'label' => $this->trans('Proportionate tax for shipping and wrapping', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('When enabled, tax for shipping and wrapping costs will be calculated proportionate to taxes applying to the products in the cart.', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
            ->add('LEGAL_MAIL_FOOTER', TranslateType::class, [
                'type' => FormattedTextareaType::class,
                'label' => $this->trans('Additional HTML in Mail-Templates', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('When enabled, tax for shipping and wrapping costs will be calculated proportionate to taxes applying to the products in the cart.', 'Modules.Legalcompliance.Admin'),
                'locales' => $this->locales,
                'hideTabs' => false,
                'required' => false,
                'options' => [
                    'constraints' => [
                        new CleanHtml([
                            'message' => $this->trans('This field is invalid', 'Modules.Legalcompliance.Admin'),
                        ]),
                    ],
                ],
            ])
        ;
    }
}
