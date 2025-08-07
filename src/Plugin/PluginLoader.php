<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Plugin;

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Plugin\PluginInterface;

class PluginLoader
{
    protected $pluginFolder;
    protected $context;

    private $loadedPlugins = null;

    public function __construct(
        string $pluginFolder,
        \Context $context
    ) {
        if (!is_dir($pluginFolder)) {
            throw new \InvalidArgumentException("Plugin folder does not exist: $pluginFolder");
        }

        if (substr($pluginFolder, -1) !== '/') {
            $pluginFolder .= '/';
        }

        $this->pluginFolder = $pluginFolder;
        $this->context = $context;
    }

    public function exec($hookName, $params = [])
    {
        try {
            $plugins = $this->loadPlugins();

            if (empty($params['context'])) {
                $params['context'] = $this->context;
            }

            $method = 'hook' . ucfirst($hookName);

            foreach ($plugins as $plugin) {
                if (method_exists($plugin, $method)) {
                    $plugin->$method($params);
                }
            }
        } catch (\Exception $e) {
            if (_PS_MODE_DEV_) {
                throw $e;
            } else {
                // Log the error or handle it gracefully
            }
        }
    }

    public function getHooksFromPluign(PluginInterface $plugin): array
    {
        $hooks = get_class_methods($plugin);

        return array_filter($hooks, function ($method) {
            return strpos($method, 'hook') === 0;
        });
    }

    public function getPluginsInformation(): array
    {
        $plugins = $this->loadPlugins();

        $info = [];
        foreach ($plugins as $pluginName => $plugin) {
            $info[$pluginName] = [
                'class' => $pluginName,
                'name' => $plugin->getName(),
                'description' => $plugin->getDescription(),
                'hooks' => $this->getHooksFromPluign($plugin),
            ];
        }

        return $info;
    }

    protected function loadPlugins()
    {
        if ($this->loadedPlugins !== null) {
            return $this->loadedPlugins;
        }

        $this->loadedPlugins = [];

        // Use Symfony Finder to get all PHP files except index.php
        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()
            ->in($this->pluginFolder)
            ->name('*.php')
            ->notName('index.php');

        foreach ($finder as $file) {
            $fileRealPath = $file->getRealPath();

            include_once $fileRealPath;

            $className = str_replace('.php', '', $file->getFilename());

            if (class_exists($className)) {
                $plugin = new $className();

                if ($plugin instanceof PluginInterface) {
                    $this->loadedPlugins[$className] = $plugin;
                }
            }
        }

        return $this->loadedPlugins;
    }
}
