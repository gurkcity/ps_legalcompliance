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

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Log\LogLevel;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

class LogFormDataProvider implements FormDataProviderInterface
{
    public function __construct(
        protected LogLevel $logLevel
    ) {
    }

    public function getData()
    {
        return [
            'loglevel' => $this->logLevel->get(),
        ];
    }

    public function setData(array $data)
    {
        $this->logLevel->set((int) $data['loglevel']);

        return [];
    }
}
