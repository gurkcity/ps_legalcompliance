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

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Exception\LogException;
use PrestaShop\PrestaShop\Core\Util\File\FileSizeConverter;
use Symfony\Component\Filesystem\Exception\IOException;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

class LogRepository
{
    private $dir = '/';
    private $filesystem;

    public function __construct(Filesystem $filesystem, string $subDir = '/')
    {
        if (substr($subDir, -1) !== '/') {
            $subDir = $subDir . '/';
        }

        global $kernel;

        if (empty($kernel)) {
            $this->dir = _PS_ROOT_DIR_ . '/var/logs/' . $subDir;
        } else {
            $this->dir = $kernel->getLogDir() . '/' . $subDir;
        }

        $this->filesystem = $filesystem;
    }

    public function getDir(): string
    {
        return $this->dir;
    }

    public function getExistingFiles(): array
    {
        $fileSizeConverter = new FileSizeConverter();

        if (!is_dir($this->dir)) {
            return [];
        }

        $result = [];

        foreach ($this->getFiles() as $file) {
            $result[] = [
                'real_path' => $file->getRealPath(),
                'filename' => $file->getFilename(),
                'size' => $fileSizeConverter->convert($file->getSize()),
            ];
        }

        return $result;
    }

    public function clear(): bool
    {
        if (!is_dir($this->dir)) {
            return true;
        }

        try {
            foreach ($this->getFiles() as $file) {
                $this->filesystem->remove($file->getPathname());
            }
        } catch (IOException $e) {
            return false;
        }

        return true;
    }

    public function delete(string $filename): bool
    {
        $realPath = $this->assertIsRealFile($filename);

        return unlink($realPath);
    }

    public function getContent(string $filename): string
    {
        $realPath = $this->assertIsRealFile($filename);

        return file_get_contents($realPath);
    }

    public function createDir(): bool
    {
        if (is_dir($this->dir)) {
            return true;
        }

        try {
            $this->filesystem->mkdir($this->dir, 0755);

            return true;
        } catch (IOException $e) {
            return false;
        }
    }

    protected function getFiles(): Finder
    {
        return Finder::create()->files()
        ->in($this->dir)
        ->depth(0)
        ->name('*.log')
        ->exclude(['index.php'])
        ->sortByName(true);
    }

    protected function assertIsRealFile(string $filename): string
    {
        if (!is_dir($this->dir)) {
            throw new LogException('the log directory does not exist');
        }

        $realPath = $this->dir . $filename;

        if (!is_file($realPath)) {
            throw new LogException('the log file does not exist');
        }

        return $realPath;
    }
}
