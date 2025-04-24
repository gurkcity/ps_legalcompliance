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
use Doctrine\DBAL\Schema\Comparator;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Schema\TableDiff;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings\SettingsInterface;

class SqlMaintenance implements MaintenanceInterface
{
    private $module;
    private $connection;
    private $dbPrefix;
    private $schema;

    public function __construct(
        \PS_Legalcompliance $module,
        Connection $connection,
        string $dbPrefix
    ) {
        $this->module = $module;
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;

        $this->schema = $this->module->getSettings()->getSql()->getSchema();
    }

    public function get(): array
    {
        $tables = [];

        foreach ($this->schema->getTables() as $table) {
            $name = $table->getName();
            $tables[] = [
                'name' => $this->stripPrefix($name),
                'valid' => $this->isTableValid($table),
            ];
        }

        return $tables;
    }

    public function getDifferencesSql(): array
    {
        $comparator = new Comparator();
        $sm = $this->connection->getSchemaManager();

        $tables = [];

        foreach ($this->schema->getTables() as $table) {
            if (!$sm->tablesExist([$table->getName()])) {
                continue;
            }

            $tables[] = $sm->listTableDetails($table->getName());
        }

        $schemaRecent = new Schema($tables);
        $schemaDiff = $comparator->compare($schemaRecent, $this->schema);

        return $schemaDiff->toSaveSql($this->connection->getDatabasePlatform());
    }

    public function reset(): bool
    {
        $sqlQueries = $this->getDifferencesSql();

        if (empty($sqlQueries)) {
            return true;
        }

        foreach ($sqlQueries as $sql_query) {
            $this->connection->executeStatement($sql_query);
        }

        return true;
    }

    public function remove(): bool
    {
        $sm = $this->connection->getSchemaManager();

        foreach ($this->schema->getTables() as $table) {
            $tableName = $table->getName();

            if (!$sm->tablesExist([$tableName])) {
                continue;
            }

            $this->connection->executeStatement('DROP TABLE `' . $this->dbPrefix . $tableName . '`');
        }

        return true;
    }

    public function isTableValid(Table $table): bool
    {
        $comparator = new Comparator();
        $sm = $this->connection->getSchemaManager();

        $tableRecent = $sm->listTableDetails($table->getName());

        /**
         * @var TableDiff|false $tableDiff
         */
        $tableDiff = $comparator->diffTable($tableRecent, $table);

        return $tableDiff === false;
    }

    public function isValid(SettingsInterface $table): bool
    {
        return true;
    }

    protected function stripPrefix(string $tableName)
    {
        return $tableName;
    }
}
