<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Maintenance;

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\Orderstate;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\SettingsInterface;

class OrderstateMaintenance implements MaintenanceInterface
{
    private $orderStates = [];
    private $module;
    private $languagesIdToIso = [];

    public function __construct(\PS_Legalcompliance $module)
    {
        $this->module = $module;
        $this->orderStates = $this->module->getSettings()->getOrderStates();

        $this->languagesIdToIso = array_column(\Language::getLanguages(), 'iso_code', 'id_lang');
    }

    public function get(): array
    {
        $orderStateMaintenance = [];

        foreach ($this->orderStates as $orderState) {
            $orderStateMaintenance[] = [
                'name' => $orderState->getName(),
                'object' => $orderState->getObject(),
                'valid' => $this->isValid($orderState),
            ];
        }

        return $orderStateMaintenance;
    }

    public function reset(): bool
    {
        $config = $this->module->getConfig();

        foreach ($this->orderStates as $orderState) {
            if ($this->isValid($orderState)) {
                continue;
            }

            $object = $orderState->getObject();
            $object->save();

            if ($orderState->getId() === 'new_order') {
                \Configuration::updateValue('OS_NEWORDER', (int) $object->id);
                $config->set('OS_NEWORDER', (int) $object->id);
            } elseif ($orderState->getId() === 'awaiting_payment') {
                $config->set('OS', (int) $object->id);
                $config->set('AWAITING_PAYMENT', 1);
            }

            foreach ($object->template as $id_lang => $template) {
                if (empty($template)) {
                    continue;
                }

                $iso_code = $this->languagesIdToIso[$id_lang] ?? '';

                if (empty($iso_code)) {
                    continue;
                }

                foreach (['html', 'txt'] as $fileending) {
                    $filename = _PS_MAIL_DIR_ . $iso_code . '/' . $template . '.' . $fileending;

                    if (is_file($filename)) {
                        continue;
                    }

                    copy($this->module->getLocalPath() . 'mails/' . $iso_code . '/' . $template . '.' . $fileending, $filename);
                }
            }
        }

        return true;
    }

    public function remove(): bool
    {
        // TODO: Implement method
        return true;
    }

    public function isValid(SettingsInterface $orderState): bool
    {
        /** @var Orderstate $orderState */
        $object = $orderState->getObject();

        if (!\Validate::isLoadedObject($object)) {
            return false;
        }

        foreach ($object->template as $id_lang => $template) {
            if (empty($template)) {
                continue;
            }

            $iso_code = $this->languagesIdToIso[$id_lang] ?? '';

            if (empty($iso_code)) {
                continue;
            }

            foreach (['html', 'txt'] as $fileending) {
                $filename = _PS_MAIL_DIR_ . $iso_code . '/' . $template . '.' . $fileending;

                if (!is_file($filename)) {
                    return false;
                }
            }
        }

        return true;
    }
}
