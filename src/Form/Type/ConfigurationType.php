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

use PrestaShopBundle\Form\Admin\Type\MultistoreConfigurationType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use PrestaShopBundle\Translation\TranslatorInterface;
use Symfony\Component\Form\FormBuilderInterface;

class ConfigurationType extends TranslatorAwareType
{
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
        /* build form here */
    }

    public function getParent(): string
    {
        return MultistoreConfigurationType::class;
    }
}
