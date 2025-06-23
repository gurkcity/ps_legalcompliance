<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */
class AeucEmailEntity extends ObjectModel
{
    public $id_mail;
    public $filename;
    public $display_name;

    public static $definition = [
        'table' => 'aeuc_email',
        'primary' => 'id',
        'fields' => [
            'id_mail' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'filename' => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 64],
            'display_name' => ['type' => self::TYPE_STRING, 'required' => true, 'size' => 64],
        ],
    ];

    public static function getAll()
    {
        return Db::getInstance()->executeS('
			SELECT *
			FROM `' . _DB_PREFIX_ . self::$definition['table'] . '`
		');
    }

    public static function getMailIdFromTplFilename(string $tpl_name)
    {
        return Db::getInstance()->getRow('
			SELECT `id_mail`
			FROM `' . _DB_PREFIX_ . self::$definition['table'] . '`
			WHERE `filename` = \'' . pSQL($tpl_name) . '\'
		');
    }
}
