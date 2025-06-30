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
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\SettingsInterface;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\Tab;
use PrestaShop\PrestaShop\Adapter\Module\Tab\ModuleTabRegister;
use PrestaShopBundle\Entity\Repository\TabRepository;
use Tab as PS_Tab;

class TabMaintenance implements MaintenanceInterface
{
    private $connection;
    private $dbPrefix = '';
    private $module;
    private $tabRepository;
    private $tabRegister;
    private $tabs = [];
    private $languages;

    private $rolesCache;

    public function __construct(
        \PS_Legalcompliance $module,
        TabRepository $tabRepository,
        ModuleTabRegister $tabRegister,
        Connection $connection,
        string $dbPrefix,
        array $languages
    ) {
        $this->module = $module;
        $this->tabRepository = $tabRepository;
        $this->tabRegister = $tabRegister;
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
        $this->languages = $languages;

        $this->tabs = $this->module->getSettings()->getTabs();
    }

    public function get(): array
    {
        $tabs = [
            'module' => [],
            'unnecessary' => [],
        ];

        foreach ($this->getTabs() as $tab) {
            $className = $tab->getClassName();

            $roles = $this->getRoles($tab);

            $tabs['module'][$className] = [
                'class_name' => $className,
                'valid' => $this->isValid($tab),
                'roles' => $roles,
            ];
        }

        $tabs['unnecessary'] = $this->getUnnecassaryTabs();

        return $tabs;
    }

    public function reset(): bool
    {
        $tabs = $this->getTabs();

        foreach ($this->getUnnecassaryTabs() as $tab_unnecessary) {
            $tab = new PS_Tab((int) $tab_unnecessary['id_tab']);
            $tab->delete();
        }

        foreach ($tabs as $tab) {
            $idTab = (int) $this->tabRepository->findOneIdByClassName($tab->getClassName());

            if (!$idTab) {
                $this->registerTab($tab);
            } else {
                $this->updateTab($tab, $idTab);

                PS_Tab::initAccess($idTab);
            }
        }

        $this->updatePositions($tabs);

        $this->invalidateCache();

        return true;
    }

    public function remove(): bool
    {
        $tabIds = $this->connection->fetchFirstColumn('
            SELECT id_tab
            FROM `' . $this->dbPrefix . 'tab`
            WHERE module = \'' . $this->module->name . '\'
        ');

        if (empty($tabIds)) {
            return true;
        }

        $tabIds = array_map('intval', $tabIds);

        $this->connection->executeStatement('
            DELETE FROM `' . $this->dbPrefix . 'tab`
            WHERE id_tab IN (' . implode(',', $tabIds) . ')
        ');

        $this->connection->executeStatement('
            DELETE FROM `' . $this->dbPrefix . 'tab_lang`
            WHERE id_tab IN (' . implode(',', $tabIds) . ')
        ');

        return true;
    }

    public function invalidateCache()
    {
        $this->rolesCache = null;
    }

    public function isValid(SettingsInterface $tab): bool
    {
        /** @var Tab $tab */
        $idTab = (int) $this->tabRepository->findOneIdByClassName($tab->getClassName());

        if (!$idTab) {
            return false;
        }

        $roles = $this->getRoles($tab);

        foreach ($roles as $role) {
            if (empty($role['id'])) {
                return false;
            }
        }

        return true;
    }

    protected function getRoles(Tab $tab): array
    {
        $tabClassName = $tab->getClassName();

        if (empty($this->rolesCache[$tabClassName])) {
            $this->rolesCache[$tabClassName] = [];

            $idTab = (int) $this->tabRepository->findOneIdByClassName($tabClassName);

            if (empty($idTab)) {
                return [];
            }

            foreach (['CREATE', 'READ', 'UPDATE', 'DELETE'] as $action) {
                $slug = \Access::sluggifyTab($tab->toArray(), $action);

                $result = (int) $this->connection->fetchOne('
                    SELECT `id_authorization_role`
                    FROM `' . $this->dbPrefix . 'authorization_role`
                    WHERE `slug` = ?
                ', [
                    $slug,
                ]);

                $this->rolesCache[$tabClassName][] = [
                    'slug' => $slug,
                    'role' => $action,
                    'id' => $result,
                ];
            }
        }

        return $this->rolesCache[$tabClassName];
    }

    protected function registerTab(Tab $tab): bool
    {
        $className = $tab->getClassName();

        if (!empty($this->tabRepository->findOneIdByClassName($className))) {
            return true;
        }

        return $this->updateTab($tab, 0);
    }

    protected function updateTab(Tab $tab, int $id)
    {
        $newTab = new PS_Tab($id);
        $newTab->active = $tab->isVisible();
        $newTab->enabled = true;
        $newTab->class_name = $tab->getClassName();
        $newTab->route_name = $tab->getRouteName();
        $newTab->module = $this->module->name;
        $newTab->name = $this->getTabNames($tab->getName());
        $newTab->icon = $tab->getIcon();
        $newTab->id_parent = $this->findParentId($tab);
        $newTab->wording = $tab->getWording();
        $newTab->wording_domain = $tab->getWordingDomain();

        return $newTab->save();
    }

    protected function updatePositions(array $tabs)
    {
        $statement = $this->connection->prepare('
            UPDATE `' . $this->dbPrefix . 'tab`
            SET `position` = :position
            WHERE `class_name` = :classname
        ');

        foreach ($tabs as $index => $tab) {
            $statement->bindValue('position', $index);
            $statement->bindValue('classname', $tab->getClassName());
            $statement->executeQuery();
        }
    }

    /**
     * Duplicate method of src\Adapter\Module\Tab\ModuleTabUnregister.php - findParentId
     */
    protected function findParentId(Tab $tab)
    {
        $idParent = 0;
        $parentClassName = $tab->getParentClassName();

        if (!empty($parentClassName)) {
            // Could be a previously duplicated tab
            $idParent = $this->tabRepository->findOneIdByClassName($parentClassName . $this->tabRegister::SUFFIX);

            if (!$idParent) {
                $idParent = $this->tabRepository->findOneIdByClassName($parentClassName);
            }
        } elseif (true === $tab->isVisible()) {
            $idParent = $this->tabRepository->findOneIdByClassName('DEFAULT');
        }

        return $idParent;
    }

    protected function getTabNames($names)
    {
        $translatedNames = [];

        foreach ($this->languages as $lang) {
            // In case we just receive a string, we apply it to all languages
            if (!is_array($names)) {
                $translatedNames[$lang['id_lang']] = $names;
            } elseif (array_key_exists($lang['locale'], $names)) {
                $translatedNames[$lang['id_lang']] = $names[$lang['locale']];
            } elseif (array_key_exists($lang['language_code'], $names)) {
                $translatedNames[$lang['id_lang']] = $names[$lang['language_code']];
            } elseif (array_key_exists($lang['iso_code'], $names)) {
                $translatedNames[$lang['id_lang']] = $names[$lang['iso_code']];
            } else {
                $translatedNames[$lang['id_lang']] = reset($names); // Get the first name available in the array
            }
        }

        return $translatedNames;
    }

    protected function getUnnecassaryTabs(): array
    {
        /*
         * Disable this functionality for now, because it is not needed
         */
        return [];

        $allTabClassNames = [];

        foreach ($this->getTabs() as $tab) {
            $allTabClassNames[] = (string) $tab;
        }

        return $this->connection->fetchAllAssociative('
            SELECT t.`id_tab`, t.`class_name`, tl.`name`
            FROM `' . $this->dbPrefix . 'tab` AS t
            LEFT JOIN `' . $this->dbPrefix . 'tab_lang` AS tl ON (
                tl.`id_tab` = t.`id_tab`
                AND tl.`id_lang` = ' . (int) \Context::getContext()->language->id . '
            )
            WHERE t.`class_name` NOT IN (\'' . implode('\',\'', $allTabClassNames) . '\')
            AND t.`module` = \'' . $this->module->name . '\'
        ');
    }

    protected function getTabs(): array
    {
        $tabs = [];

        foreach ($this->tabs as $tab) {
            if ($tab->getParentClassName() === 'AdminParentModulesSf') {
                $className = $tab->getClassName();
                $parentClassName = $className . $this->tabRegister::SUFFIX;

                $newTab = clone $tab;
                $newTab->setClassName($parentClassName);
                $tabs[] = $newTab;

                $newTab = clone $tab;
                $newTab->setParentClassName($parentClassName);
                $tabs[] = $newTab;

                continue;
            }

            $tabs[] = clone $tab;
        }

        return $tabs;
    }
}
