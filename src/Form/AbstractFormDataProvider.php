<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form;

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Module\ConfigurationAdapter;
use PrestaShop\PrestaShop\Adapter\Feature\MultistoreFeature;
use PrestaShop\PrestaShop\Adapter\Shop\Context;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;
use PrestaShopBundle\Service\Form\MultistoreCheckboxEnabler;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractFormDataProvider
{
    public function __construct(
        protected ConfigurationAdapter $configurationAdapter,
        protected Context $shopContext,
        protected MultistoreFeature $multistoreFeature,
        protected TranslatorInterface $translator
    ) {
    }

    protected function updateConfiguration(
        string $key,
        string $fieldName,
        $value,
        ?ShopConstraint $shopConstraint = null,
        array $options = []
    ) {
        $multistoreFieldPrefix = MultistoreCheckboxEnabler::MULTISTORE_FIELD_PREFIX;

        $configPrefix = isset($options['prefix']) ? $options['prefix'] : true;

        if (
            $this->multistoreFeature->isUsed()
            && !$this->shopContext->isAllShopContext()
            && !isset($value[$multistoreFieldPrefix . $fieldName])
        ) {
            $this->configurationAdapter->deleteFromContext($key, $shopConstraint, $configPrefix);
        } else {
            $this->configurationAdapter->set($key, $value, $options['html'] ?? false, $shopConstraint, $configPrefix);
        }
    }
}
