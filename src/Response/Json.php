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

class Json
{
    protected $status = 'ok';
    protected $message = '';
    protected $data = [];

    public function setStatus(bool $status): self
    {
        $this->status = $status ? 'ok' : 'ko';

        return $this;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function setData(array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getContent(): array
    {
        return [
            'status' => $this->status,
            'message' => $this->message,
            'data' => $this->data,
        ];
    }

    public function getJson(): string
    {
        return json_encode($this->getContent());
    }

    public function echo()
    {
        if (!headers_sent()) {
            header('Content-Type: application/json');
        }

        echo $this->getJson();
    }

    public function __toString()
    {
        return $this->getJson();
    }
}
