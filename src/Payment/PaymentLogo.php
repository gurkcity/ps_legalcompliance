<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Payment;

class PaymentLogo
{
    const DEFAULT_LOGO_NAME = 'views/img/payment.png';
    const LOGO_DIR = 'views/img/';

    private $path;
    private $pathUri;
    private $logoName;

    public function __construct(
        string $path,
        string $pathUri,
        string $logoName
    ) {
        $this->path = $path;
        $this->pathUri = $pathUri;
        $this->logoName = $logoName;

        if (substr($this->path, -1) !== '/') {
            $this->path . '/';
        }

        if (substr($this->pathUri, -1) !== '/') {
            $this->pathUri . '/';
        }
    }

    public function getFilePath(): string
    {
        $logoDir = $this->path . self::LOGO_DIR . $this->logoName;

        if (!is_file($logoDir)) {
            $logoDir = $this->path . self::DEFAULT_LOGO_NAME;
        }

        return $logoDir;
    }

    public function getFilePathUri(): string
    {
        $logoDir = $this->path . self::LOGO_DIR . $this->logoName;

        if (!is_file($logoDir)) {
            return $this->pathUri . self::DEFAULT_LOGO_NAME;
        }

        return $this->pathUri . self::LOGO_DIR . $this->logoName;
    }

    public function getPath(): string
    {
        return $this->path . self::LOGO_DIR;
    }

    public function getPathUri(): string
    {
        return $this->pathUri . self::LOGO_DIR;
    }

    public function getRandomFilename(): string
    {
        return uniqid();
    }

    public function getImageSize(): array
    {
        $file = $this->getFilePath();

        return getimagesize($file);
    }
}
