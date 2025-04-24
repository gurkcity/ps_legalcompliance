<?php

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\DataProvider;

use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;

class VirtualDataProvider implements FormDataProviderInterface
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
        $labelText = $this->configuration->get('AEUC_VP_LABEL_TEXT');

        if (!is_array($labelText)) {
            $tempLabelText = [];

            foreach ($this->languages as $lang) {
                $tempLabelText[$lang['id_lang']] = $labelText;
            }

            $labelText = $tempLabelText;
        }

        return [
            'AEUC_VP_ACTIVE' => $this->configuration->get('AEUC_VP_ACTIVE'),
            'AEUC_VP_CMS_ID' => $this->configuration->get('AEUC_VP_CMS_ID'),
            'AEUC_VP_LABEL_TEXT' => $labelText,
            'AEUC_LABEL_REVOCATION_VP' => $this->configuration->get('AEUC_LABEL_REVOCATION_VP'),
        ];
    }

    public function setData(array $data)
    {
        $label_text = [];

        foreach ($this->languages as $lang) {
            $label_text[(int) $lang['id_lang']] = trim($data['AEUC_VP_LABEL_TEXT_' . (int) $lang['id_lang']] ?? '');
        }

        $this->configuration->set('AEUC_VP_ACTIVE', (bool) $data['AEUC_VP_ACTIVE']);
        $this->configuration->set('AEUC_VP_CMS_ID', (int) $data['AEUC_VP_CMS_ID']);
        $this->configuration->set('AEUC_VP_LABEL_TEXT', $label_text);
        $this->configuration->set('AEUC_LABEL_REVOCATION_VP', (bool) $data['AEUC_LABEL_REVOCATION_VP']);

        return [];
    }
}
