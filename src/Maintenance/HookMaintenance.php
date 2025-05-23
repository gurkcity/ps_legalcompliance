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

use Doctrine\DBAL\Connection;
use Hook as PS_Hook;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\Hook;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\SettingsInterface;

class HookMaintenance implements MaintenanceInterface
{
    private $module;
    private $idShop;
    private $connection;
    private $dbPrefix = '';
    private $hooks = [];

    public function __construct(
        \PS_Legalcompliance $module,
        int $idShop,
        Connection $connection,
        string $dbPrefix
    ) {
        $this->module = $module;
        $this->idShop = $idShop;
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;

        $this->hooks = $this->module->getSettings()->getHooks();
    }

    public function get(): array
    {
        $hooks = [
            'module' => [],
            'unnecessary' => [],
        ];

        foreach ($this->hooks as $hook) {
            $hooks['module'][(string) $hook] = [
                'name' => (string) $hook,
                'registered' => $this->isValid($hook),
                'alternatives' => $hook->getAlternatives(),
            ];
        }

        $hooks['unnecessary'] = $this->getUnnecassaryHooks();

        ksort($hooks['module']);
        sort($hooks['unnecessary']);

        return $hooks;
    }

    public function reset(): bool
    {
        $registered = true;

        foreach ($this->hooks as $hook) {
            if ($this->isValid($hook)) {
                continue;
            }

            $registered = $this->module->registerHook((string) $hook) && $registered;
        }

        foreach ($this->getUnnecassaryHooks() as $hooksUnnessesary) {
            $this->module->unregisterHook($hooksUnnessesary) && $registered;
        }

        return $registered;
    }

    public function remove(): bool
    {
        $idModule = (int) $this->connection->fetchOne('
            SELECT id_module
            FROM `' . $this->dbPrefix . 'module`
            WHERE name = \'' . $this->module->name . '\'
        ');

        if (empty($idModule)) {
            return true;
        }

        $this->connection->executeStatement('
            DELETE FROM `' . $this->dbPrefix . 'hook_module`
            WHERE id_module = ' . $idModule . '
        ');

        return true;
    }

    public function isValid(SettingsInterface $hook): bool
    {
        /** @var Hook $hook */
        if (PS_Hook::isModuleRegisteredOnHook(
            $this->module,
            (string) $hook,
            $this->idShop
        )) {
            return true;
        }

        foreach ($hook->getAlternatives() as $alternative) {
            if (PS_Hook::isModuleRegisteredOnHook(
                $this->module,
                (string) $alternative,
                $this->idShop
            )) {
                return true;
            }
        }

        return false;
    }

    protected function getUnnecassaryHooks(): array
    {
        $allHookNames = [];

        foreach ($this->hooks as $hook) {
            $allHookNames[] = (string) $hook;

            foreach ($hook->getAlternatives() as $alternativeHookNames) {
                $allHookNames[] = $alternativeHookNames;
            }
        }

        $allHookNames = array_unique($allHookNames);

        return $this->connection->fetchFirstColumn('
            SELECT h.`name`
            FROM `' . $this->dbPrefix . 'hook_module` AS hm
            LEFT JOIN `' . $this->dbPrefix . 'hook` AS h ON (h.`id_hook` = hm.`id_hook`)
            WHERE hm.`id_hook` IN (
                SELECT `id_hook`
                FROM `' . $this->dbPrefix . 'hook`
                WHERE `name` NOT IN (\'' . implode('\',\'', $allHookNames) . '\')
            )
            AND hm.`id_module` = ' . (int) $this->module->id . '
        ');
    }
}
