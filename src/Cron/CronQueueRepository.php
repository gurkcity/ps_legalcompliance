<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Cron;

use Doctrine\DBAL\Connection;
use PrestaShop\PrestaShop\Core\Module\Legacy\ModuleInterface;

class CronQueueRepository
{
    const CLEANUP_TIME = 60 * 60 * 24;
    const RUNTIME_PRECITION = 3;

    private $connection;
    private $dbPrefix = '';
    private $module;

    public function __construct(
        Connection $connection,
        string $dbPrefix,
        ModuleInterface $module
    ) {
        $this->connection = $connection;
        $this->dbPrefix = $dbPrefix;
        $this->module = $module;
    }

    public function push(
        string $value,
        int $priority = 1,
        string $type = ''
    ): bool {
        if ($priority < 0 || $priority > 9) {
            throw new \Exception('push cron priority is not between 0 and 9');
        }

        $isPresent = (bool) $this->connection->fetchOne('
            SELECT 1 FROM `' . $this->getTableName() . '`
            WHERE `value` = :value
            AND `executed` = 0
            AND `type` = :type
        ', [
            'value' => $value,
            'type' => $type ?: null,
        ]);

        if (!$isPresent) {
            $this->connection->executeQuery('
                INSERT IGNORE INTO `' . $this->getTableName() . '` SET
                `value` = :value,
                `date_add` = :date_add,
                `executed` = 0,
                `runtime` = 0,
                `priority` = :priority,
                `type` = :type
            ', [
                'value' => $value,
                'date_add' => date('Y-m-d H:i:s'),
                'priority' => $priority,
                'type' => $type ?: null,
            ]);

            return true;
        }

        return false;
    }

    public function pushMultiple(
        array $values,
        int $priority = 1,
        string $type = ''
    ): bool {
        if (!$values) {
            return true;
        }

        if ($priority < 0 || $priority > 9) {
            throw new \Exception('push cron priority is not between 0 and 9');
        }

        $now = date('Y-m-d H:i:s');

        $isPresent = $this->connection->fetchOne('
            SELECT `value`
            FROM `' . $this->getTableName() . '`
            WHERE `value` IN (?)
            AND `executed` = 0
            AND `type` = ?
        ', [
            $values,
            $type === '' ? null : $type,
        ], [
            Connection::PARAM_STR_ARRAY,
            \PDO::PARAM_STR,
        ]);

        $sqlReplace = [];

        foreach ($values as $value) {
            if (in_array($value, $isPresent)) {
                continue;
            }

            $sqlReplace[] = [
                'value' => $value,
                'date_add' => $now,
                'executed' => 0,
                'runtime' => 0,
                'priority' => $priority,
                'type' => ($type === '' ? null : $type),
            ];
        }

        if ($sqlReplace) {
            $this->connection->insert($this->getTableName(), $sqlReplace);
        }

        return true;
    }

    public function pull(): array
    {
        $nextCron = $this->connection->fetchAssociative('
            SELECT `id_cron_queue`, `value`, `type`
            FROM `' . $this->getTableName() . '`
            WHERE `executed` = 0
            ORDER BY `priority` ASC, `id_cron_queue` ASC
            LIMIT 1
        ');

        if (!$nextCron) {
            return [];
        }

        $this->connection->update(
            $this->getTableName(),
            [
                'executed' => -1,
            ], [
                'id_cron_queue' => $nextCron['id_cron_queue'],
            ]
        );

        return $nextCron;
    }

    public function finish(int $idCronQueue, float $runtime)
    {
        return $this->connection->update(
            $this->getTableName(), [
                'executed' => 1,
                'runtime' => round($runtime, self::RUNTIME_PRECITION),
            ], [
                'id_cron_queue' => $idCronQueue,
            ]
        );
    }

    public function cleanup(): bool
    {
        $now = new \DateTime();
        $now->modify('-' . self::CLEANUP_TIME . ' second');

        $this->connection->executeStatement('
            DELETE FROM `' . $this->getTableName() . '`
            WHERE `executed` != 0
            AND `date_add` <= :date_add
        ', [
            'date_add' => $now->format('Y-m-d H:i:s'),
        ]);

        return true;
    }

    public function getStats()
    {
        return $this->connection->fetchAllAssociative('
            SELECT
                a.`executed`,
                a.`type`,
                AVG(a.`runtime`) AS `average_runtime`,
                MAX(a.`runtime`) AS `max_runtime`,
                MIN(a.`runtime`) AS `min_runtime`,
                COUNT(a.`id_cron_queue`) AS `count`
            FROM `' . $this->getTableName() . '` AS a
            GROUP BY a.`type`, a.`executed`
            ORDER BY a.`executed`, a.`type`
        ');
    }

    public function getTableName(): string
    {
        return bqSQL($this->dbPrefix . $this->module->name) . '_cron_queue';
    }
}
