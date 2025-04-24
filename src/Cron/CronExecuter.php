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

use Monolog\Logger;

class CronExecuter
{
    private $cronQueueRepository;
    private $cronRuntime = 1;
    private $currentJob;
    private $timeStart = 0.0;
    private $logger;

    public function __construct(
        CronQueueRepository $cronQueueRepository,
        int $cronRuntime,
        Logger $logger
    ) {
        $this->cronQueueRepository = $cronQueueRepository;
        $this->cronRuntime = $cronRuntime;
        $this->logger = $logger;

        if ($this->cronRuntime < 60) {
            $this->cronRuntime = 60;
        }

        $this->cronQueueRepository->cleanup();
    }

    public function runSingle($function)
    {
        $this->currentJob = $this->cronQueueRepository->pull();

        if (!$this->currentJob) {
            return 0;
        }

        $this->timeStart = microtime(true);

        $result = call_user_func($function, $this->currentJob['value'], $this->currentJob['type'], $this->logger);

        $runtime = microtime(true) - $this->timeStart;

        $this->cronQueueRepository->finish(
            (int) $this->currentJob['id_cron_queue'],
            $runtime
        );

        $this->currentJob = null;

        return $result ? 1 : 0;
    }

    public function run($function): int
    {
        $diff = 0;

        $countJobsTotal = 0;

        do {
            $timeStart = microtime(true);

            $countJobsExecuted = $this->runSingle($function);

            if ($countJobsExecuted === 0) {
                return $countJobsTotal;
            }

            $countJobsTotal += $countJobsExecuted;

            $timeEnd = microtime(true);

            $diff += $timeEnd - $timeStart;
        } while (($diff + 5) < $this->cronRuntime);

        return $countJobsTotal;
    }

    public function __destruct()
    {
        if ($this->currentJob) {
            $runtime = (int) (microtime(true) - $this->timeStart);

            $this->cronQueueRepository->finish(
                (int) $this->currentJob['id_cron_queue'],
                $runtime
            );

            $this->currentJob = null;
        }
    }
}
