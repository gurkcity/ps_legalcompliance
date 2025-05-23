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

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Payment\PaymentLogoFactory;
use PrestaShopBundle\Form\Admin\Type\MultistoreConfigurationType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use PrestaShopBundle\Translation\TranslatorInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\File;

class PaymentType extends TranslatorAwareType
{
    protected $configurationAdapter;
    protected $context;
    protected $config;
    protected $module;

    public function __construct(
        TranslatorInterface $translator,
        array $locales,
        \Context $context,
        \PS_Legalcompliance $module
    ) {
        parent::__construct($translator, $locales);

        $this->context = $context;
        $this->module = $module;
        $this->config = $this->module->config;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $orderStates = \OrderState::getOrderStates($this->context->language->id);
        $orderStatesChoicesList = $this->formatDataChoicesList($orderStates, 'id_order_state', 'name');

        $paymentLogo = (new PaymentLogoFactory($this->module))->getPaymentLogo();

        $size = $paymentLogo->getImageSize();
        $width = $size[0] ?? 0;
        $height = $size[1] ?? 0;

        $builder->add('payment_logo', ImageFileType::class, [
            'label' => $this->trans('Payment Logo', 'Modules.Pslegalcompliance.Admin'),
            'help' => $this->trans('Upload custom payment logo. The logo will appear on the checkout page', 'Modules.Pslegalcompliance.Admin'),
            'constraints' => [
                new File([
                    'maxSize' => '5000k',
                    'mimeTypes' => [
                        'image/*',
                    ],
                    'mimeTypesMessage' => $this->trans('Please upload a valid image file', 'Modules.Pslegalcompliance.Admin'),
                ]),
            ],
            'mapped' => false,
            'image_file' => $paymentLogo->getFilePathUri(),
            'image_width' => $width,
            'image_height' => $height,
        ]);
        $builder->add('os_neworder', ChoiceType::class, [
            'label' => $this->trans('Order State after placing an order', 'Modules.Pslegalcompliance.Admin'),
            'choices' => $orderStatesChoicesList,
            'help' => $this->trans('The order state after a customer placed an order.', 'Modules.Pslegalcompliance.Admin'),
            'constraints' => [
                new Choice([
                    'choices' => $orderStatesChoicesList,
                    'message' => $this->trans('Please select a valid order state.', 'Modules.Pslegalcompliance.Admin'),
                ]),
            ],
            'multistore_configuration_key' => $this->config->getName('OS_NEWORDER'),
        ]);
        $builder->add('awaiting_payment', SwitchType::class, [
            'label' => $this->trans('Order State Awaiting Payment', 'Modules.Pslegalcompliance.Admin'),
            'help' => $this->trans('Automatically set the order state to \'awaiting payment\' after order was created.', 'Modules.Pslegalcompliance.Admin'),
            'multistore_configuration_key' => $this->config->getName('AWAITING_PAYMENT'),
        ]);
        $builder->add('os', ChoiceType::class, [
            'label' => $this->trans('Order State Awaiting Payment', 'Modules.Pslegalcompliance.Admin'),
            'choices' => $orderStatesChoicesList,
            'help' => $this->trans('The order state after customer placed an order and waiting for payment.', 'Modules.Pslegalcompliance.Admin'),
            'constraints' => [
                new Choice([
                    'choices' => $orderStatesChoicesList,
                    'message' => $this->trans('Please select a valid order state.', 'Modules.Pslegalcompliance.Admin'),
                ]),
            ],
            'multistore_configuration_key' => $this->config->getName('OS'),
        ]);
        $builder->add('show_payment_logo', SwitchType::class, [
            'label' => $this->trans('Show Payment Logo', 'Modules.Pslegalcompliance.Admin'),
            'help' => $this->trans('Display the payment logo on checkout payment selection.', 'Modules.Pslegalcompliance.Admin'),
            'multistore_configuration_key' => $this->config->getName('SHOW_PAYMENT_LOGO'),
        ]);
    }

    public function getParent(): string
    {
        return MultistoreConfigurationType::class;
    }
}
