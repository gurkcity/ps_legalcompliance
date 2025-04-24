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
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\Controller;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\SettingsInterface;

class ControllerMaintenance implements MaintenanceInterface
{
    private $module;
    private $connection;
    private $dbPrefix = '';
    private $controller = [];

    public function __construct(
        \PS_Legalcompliance $module,
        Connection $connection,
        string $dbPrefix
    ) {
        $this->module = $module;
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;

        $this->controller = $this->module->getSettings()->getControllers();
    }

    public function get(): array
    {
        $result = [];

        foreach ($this->controller as $controller) {
            $controllerName = (string) $controller;

            $result[$controllerName] = [
                'name' => $controller,
                'valid' => $this->isValid($controller),
            ];
        }

        ksort($result);

        return $result;
    }

    public function reset(): bool
    {
        foreach ($this->controller as $controller) {
            if ($this->isValid($controller)) {
                continue;
            }

            $page = $this->getPageName($controller);

            $meta = new \Meta();
            $meta->page = $page;
            $meta->configurable = 1;
            $meta->save();
        }

        return true;
    }

    public function remove(): bool
    {
        $pages = [];

        foreach ($this->controller as $controller) {
            if ($this->isValid($controller)) {
                continue;
            }

            $pages[] = $this->getPageName($controller);
        }

        if (empty($pages)) {
            return true;
        }

        $metaIds = $this->connection->fetchFirstColumn('
            SELECT id_meta
            FROM ' . $this->dbPrefix . 'meta
            WHERE page IN (\'' . implode('\',\'', $pages) . '\')
        ');

        if (empty($metaIds)) {
            return true;
        }

        $metaIds = array_map('intval', $metaIds);

        $this->connection->executeStatement('
            DELETE FROM ' . $this->dbPrefix . 'meta
            WHERE id_meta IN (' . implode(',', $metaIds) . ')
        ');

        $this->connection->executeStatement('
            DELETE FROM ' . $this->dbPrefix . 'meta_lang
            WHERE id_meta IN (' . implode(',', $metaIds) . ')
        ');

        return true;
    }

    public function isValid(SettingsInterface $controller): bool
    {
        /**
         * @var Controller $controller
         */
        $page = $this->getPageName((string) $controller);

        return (bool) $this->connection->fetchOne('
            SELECT 1
            FROM ' . $this->dbPrefix . 'meta
            WHERE page = :page
        ', [
            'page' => $page,
        ]);
    }

    private function getPageName(string $controllerName): string
    {
        return 'module-' . $this->module->name . '-' . $controllerName;
    }
}
