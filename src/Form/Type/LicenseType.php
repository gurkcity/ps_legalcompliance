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

use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class LicenseType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('license', TextType::class, [
            'label' => $this->trans('License Code', 'Modules.Legalcompliance.Admin'),
            'constraints' => [
                new Regex([
                    'pattern' => '/^\s?[0-9a-z]{16}\s?$/i',
                    'message' => $this->trans('This license code seems not to be valid', 'Modules.Legalcompliance.Admin'),
                ]),
            ],
        ])->add('privacy', CheckboxType::class, [
            'label' => $this->trans('Yes! I Accept the privacy policy.', 'Modules.Legalcompliance.Admin'),
            'help' => $this->trans(
                'If you click on \'save and continue\' some parameters of your onlinestore will be send to the domain https://www.onlineshop-module.de and your license will be activatet. This parameters contains: Licensecode, referer of your Browser, domain of your onlienestore, version of this module.',
                'Modules.Legalcompliance.Admin'
            ),
            'constraints' => [
                new NotBlank([
                    'message' => $this->trans('Please accept the privacy policy', 'Modules.Legalcompliance.Admin'),
                ]),
            ],
        ]);
    }
}
