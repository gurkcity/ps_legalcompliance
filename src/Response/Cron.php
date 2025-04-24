<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Response;

class Cron extends Json
{
    const RUNTIME_ROUND = 3;

    protected $runtime = 0.0;
    protected $action = '';
    protected $force = false;
    protected $queue = false;

    public function setRuntime(float $runtime): self
    {
        $this->runtime = round($runtime, self::RUNTIME_ROUND);

        return $this;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function setForce(bool $force): self
    {
        $this->force = $force;

        return $this;
    }

    public function setQueue(bool $queue): self
    {
        $this->queue = $queue;

        return $this;
    }

    public function getContent(): array
    {
        return array_merge(
            parent::getContent(),
            [
                'runtime' => $this->runtime,
                'action' => $this->action,
                'force' => $this->force,
                'queue' => $this->queue,
            ]
        );
    }
}
