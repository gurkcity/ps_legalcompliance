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

class CronFormDataProvider extends AbstractFormDataProvider implements FormDataProviderInterface
{
    public function getData()
    {
        return [
            'maintenance' => (bool) $this->configurationAdapter->getGlobal('CRON_MAINTENANCE'),
            'rows_per_run' => (int) $this->configurationAdapter->getGlobal('CRON_ROWS_PER_RUN'),
        ];
    }

    public function setData(array $data)
    {
        $this->configurationAdapter->setGlobal('CRON_MAINTENANCE', (bool) $data['maintenance']);

        if (isset($data['rows_per_run'])) {
            $this->configurationAdapter->setGlobal('CRON_ROWS_PER_RUN', (int) $data['rows_per_run']);
        }

        return [];
    }
}
