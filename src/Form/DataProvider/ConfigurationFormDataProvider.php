<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\DataProvider;

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\AbstractFormDataProvider;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

class ConfigurationFormDataProvider extends AbstractFormDataProvider implements FormDataProviderInterface
{
    public function getData()
    {
        return [
            'example_text' => $this->configurationAdapter->get('EXAMPLE_TEXT'),
        ];
    }

    public function setData(array $data)
    {
        $this->updateConfiguration('EXAMPLE_TEXT', 'example_text', $data['example_text']);

        return [];
    }
}
