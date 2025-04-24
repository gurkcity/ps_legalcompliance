<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Log;

use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger as Monolog;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Exception\LogException;

class Logger
{
    const PRESERVE_DAYS = 365;

    private $logRepository;
    private $logLevel;
    private $instances = [];

    public function __construct(LogRepository $logRepository, LogLevel $logLevel)
    {
        $this->logRepository = $logRepository;
        $this->logLevel = $logLevel;
    }

    public function __get($name)
    {
        return $this->getInstanceOrCreate($name);
    }

    public function getInstanceOrCreate(string $name)
    {
        if (!isset($this->instances[$name])) {
            $this->instances[$name] = $this->createNewLoggerInstance($name);
        }

        return $this->instances[$name];
    }

    public function getInstances()
    {
        return $this->instances;
    }

    public function setLevel(int $level, string $name = '')
    {
        if ($name) {
            $instances = [$this->getInstanceOrCreate($name)];
        } else {
            $instances = $this->getInstances();
        }

        foreach ($instances as $instance) {
            $handlers = $instance->getHandlers();

            foreach ($handlers as $handler) {
                $handler->setLevel($level);
            }
        }
    }

    public function createNewLoggerInstance(string $loggerName): Monolog
    {
        if (!$this->isLoggerNameValid($loggerName)) {
            throw new LogException('Log name is not valid!');
        }

        $this->logRepository->createDir();

        $logger = new Monolog($loggerName);
        $logger->pushHandler(
            new RotatingFileHandler(
                $this->logRepository->getDir() . $loggerName . '.log',
                self::PRESERVE_DAYS,
                $this->logLevel->get()
            )
        );

        return $logger;
    }

    public function isLoggerNameValid(string $loggerName): bool
    {
        return preg_match('/^[a-z]{3,}$/', $loggerName);
    }
}
