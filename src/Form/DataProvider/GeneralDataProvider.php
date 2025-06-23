<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\DataProvider;

use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

class GeneralDataProvider implements FormDataProviderInterface
{
    private $configuration;
    private $languages;

    public function __construct(Configuration $configuration, array $languages)
    {
        $this->configuration = $configuration;
        $this->languages = $languages;
    }

    public function getData()
    {
        return [
            'AEUC_FEAT_REORDER' => !$this->configuration->get('PS_DISALLOW_HISTORY_REORDERING'),
            'PS_ATCP_SHIPWRAP' => $this->configuration->get('PS_ATCP_SHIPWRAP'),
            'LEGAL_MAIL_FOOTER' => $this->configuration->get('LEGAL_MAIL_FOOTER'),
        ];
    }

    public function setData(array $data)
    {
        $legalMailFooter = [];

        foreach ($this->languages as $lang) {
            $legalMailFooter[(int) $lang['id_lang']] = trim($data['LEGAL_MAIL_FOOTER'][$lang['id_lang']] ?? '');
        }

        $this->configuration->set('PS_DISALLOW_HISTORY_REORDERING', !((bool) $data['AEUC_FEAT_REORDER']));
        $this->configuration->set('PS_ATCP_SHIPWRAP', (bool) $data['PS_ATCP_SHIPWRAP']);
        $this->configuration->set('LEGAL_MAIL_FOOTER', $legalMailFooter, null, ['html' => true]);

        return [];
    }
}
