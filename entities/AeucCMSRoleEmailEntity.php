<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */
class AeucCMSRoleEmailEntity extends ObjectModel
{
    public $id_cms_role;
    public $id_mail;

    public static $definition = [
        'table' => 'aeuc_cmsrole_email',
        'primary' => 'id',
        'fields' => [
            'id_mail' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
            'id_cms_role' => ['type' => self::TYPE_INT, 'validate' => 'isUnsignedInt'],
        ],
    ];

    public static function truncate()
    {
        return Db::getInstance()->execute('
			TRUNCATE `' . _DB_PREFIX_ . self::$definition['table'] . '`
		');
    }

    public static function getIdEmailFromCMSRoleId(int $id_cms_role)
    {
        return Db::getInstance()->executeS('
			SELECT `id_mail`
			FROM `' . _DB_PREFIX_ . self::$definition['table'] . '`
			WHERE `id_cms_role` = ' . $id_cms_role . '
		');
    }

    public static function getAll()
    {
        return Db::getInstance()->executeS('
			SELECT *
			FROM `' . _DB_PREFIX_ . self::$definition['table'] . '`
		');
    }

    public static function getCMSRoleIdsFromIdMail(int $id_mail)
    {
        return Db::getInstance()->executeS('
			SELECT DISTINCT(`id_cms_role`)
			FROM `' . _DB_PREFIX_ . self::$definition['table'] . '`
			WHERE `id_mail` = ' . $id_mail . '
		');
    }
}
