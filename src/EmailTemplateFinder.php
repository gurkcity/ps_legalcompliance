<?php

namespace PSLegalcompliance;

use AeucEmailEntity;
use Configuration;
use Language;
use LegalcomplianceException;
use PrestaShop\PrestaShop\Core\Email\EmailLister;

class EmailTemplateFinder
{
    protected $emailLister;

    public function __construct(EmailLister $emailLister)
    {
        $this->emailLister = $emailLister;
    }

    public function findNewEmailTemplates(): array
    {
        $defaultEmailTemplatePath = $this->getDefaultEmailTemplatePath();
        $allAvailableEmailTemplates = $this->getAllAvailableEmailTemplates($defaultEmailTemplatePath);

        return $this->filterNewEmailTemplates($allAvailableEmailTemplates);
    }

    public function getAllAvailableEmailTemplates(string $emailPath): array
    {
        if (!is_dir($emailPath)) {
            throw new LegalcomplianceException(sprintf('Email template path %s is not vaild', $emailPath));
        }

        return $this->emailLister->getAvailableMails($emailPath);
    }

    public function getDefaultEmailTemplatePath(): string
    {
        $defaultEmailTemplatePath = _PS_MAIL_DIR_ . 'en';

        if (!is_dir($defaultEmailTemplatePath)) {
            $langIso = $this->getIsoFromDefaultLanguage();
            $defaultEmailTemplatePath = _PS_MAIL_DIR_ . $langIso;
        }

        if (!is_dir($defaultEmailTemplatePath)) {
            return '';
        }

        return $defaultEmailTemplatePath;
    }

    protected function getIsoFromDefaultLanguage(): string
    {
        $idLangDefault = (int) Configuration::get('PS_LANG_DEFAULT');

        return Language::getIsoById($idLangDefault);
    }

    protected function filterNewEmailTemplates(array $emailTemplates): array
    {
        $currentEmailTemplates = $this->getStoredEmailTemplates();

        return array_diff($emailTemplates, $currentEmailTemplates);
    }

    protected function getStoredEmailTemplates(): array
    {
        $allEmailTemplates = AeucEmailEntity::getAll();

        return array_map(function ( $emailTemplate) {
            return $emailTemplate['filename'];
        }, $allEmailTemplates);
    }
}
