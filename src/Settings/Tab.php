<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Settings;

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Exception\SettingException;

class Tab implements SettingsInterface
{
    private $name = [];
    private $class_name = '';
    private $parentClassName = '';
    private $routeName = '';
    private $icon = '';
    private $wording = '';
    private $wordingDomain = '';
    private $visible = true;

    public function __construct(
        array $name,
        string $className,
        string $parentClassName,
        string $routeName = '',
        string $icon = '',
        string $wording = '',
        string $wordingDomain = '',
        bool $visible = true
    ) {
        self::assertName($className);
        self::assertName($parentClassName);

        $this->name = $name;
        $this->class_name = $className;
        $this->parentClassName = $parentClassName;
        $this->routeName = $routeName;
        $this->icon = $icon;
        $this->wording = $wording;
        $this->wordingDomain = $wordingDomain;
        $this->visible = $visible;
    }

    public static function buildFromArray(array $tab)
    {
        $name = $tab['name'] ?? $tab['wording'] ?? '';
        $name_array = [];

        if (is_string($name)) {
            foreach (\Language::getLanguages() as $lang) {
                $name_array[$lang['iso_code']] = $name;
            }
        } elseif (is_array($name)) {
            $name_array = $name;

            if (!isset($name['en'])) {
                $name_array['en'] = reset($name);
            }
        } else {
            throw new SettingException('Tab build from array failed');
        }

        return new self(
            $name_array,
            $tab['class_name'] ?? '',
            $tab['parent_class_name'] ?? '',
            $tab['route_name'] ?? '',
            $tab['icon'] ?? '',
            $tab['wording'] ?? '',
            $tab['wording_domain'] ?? '',
            $tab['visible'] ?? true,
        );
    }

    public function getName(): array
    {
        return $this->name;
    }

    public function getClassName(): string
    {
        return $this->class_name;
    }

    public function setClassName($className): self
    {
        self::assertName($className);

        $this->class_name = $className;

        return $this;
    }

    public function setParentClassName($className): self
    {
        self::assertName($className);

        $this->parentClassName = $className;

        return $this;
    }

    public function getParentClassName(): string
    {
        return $this->parentClassName;
    }

    public function getRouteName(): string
    {
        return $this->routeName;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getWording(): string
    {
        return $this->wording;
    }

    public function getWordingDomain(): string
    {
        return $this->wordingDomain;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function toArray(): array
    {
        return [
            'class_name' => $this->getClassName(),
            'route_name' => $this->getRouteName(),
            'icon' => $this->getIcon(),
            'wording' => $this->getWording(),
            'wording_domain' => $this->getWordingDomain(),
            'visible' => $this->isVisible(),
            'parent_class_name' => $this->getParentClassName(),
            'name' => $this->getName(),
        ];
    }

    public function __toString()
    {
        return $this->getClassName();
    }

    public static function assertName($name)
    {
        if (!preg_match('/^[^0-9!<>,;?=+()@#"°{}$%:¤|]*$/u', $name)) {
            throw new SettingException('Tab name is not valid');
        }
    }
}
