<?php

namespace PSLegalcompliance;

use Symfony\Contracts\Translation\TranslatorInterface;

final class Roles
{
    const NO_ASSOC = 'NO_ASSOC';
    const NOTICE = 'LEGAL_NOTICE';
    const CONDITIONS = 'LEGAL_CONDITIONS';
    const REVOCATION = 'LEGAL_REVOCATION';
    const REVOCATION_FORM = 'LEGAL_REVOCATION_FORM';
    const PRIVACY = 'LEGAL_PRIVACY';
    const ENVIRONMENTAL = 'LEGAL_ENVIRONMENTAL';
    const SHIP_PAY = 'LEGAL_SHIP_PAY';

    public static function getAll(): array
    {
        return [
            self::NOTICE,
            self::CONDITIONS,
            self::REVOCATION,
            self::REVOCATION_FORM,
            self::PRIVACY,
            self::ENVIRONMENTAL,
            self::SHIP_PAY
        ];
    }

    public static function getTranslated(TranslatorInterface $translator): array
    {
        return [
            Roles::NOTICE => $translator->trans('Legal notice', [], 'Modules.Legalcompliance.Admin'),
            Roles::CONDITIONS => $translator->trans('Terms of Service (ToS)', [], 'Modules.Legalcompliance.Admin'),
            Roles::REVOCATION => $translator->trans('Revocation terms', [], 'Modules.Legalcompliance.Admin'),
            Roles::REVOCATION_FORM => $translator->trans('Revocation form', [], 'Modules.Legalcompliance.Admin'),
            Roles::PRIVACY => $translator->trans('Privacy', [], 'Modules.Legalcompliance.Admin'),
            Roles::ENVIRONMENTAL => $translator->trans('Environmental notice', [], 'Modules.Legalcompliance.Admin'),
            Roles::SHIP_PAY => $translator->trans('Shipping and payment', [], 'Modules.Legalcompliance.Admin'),
        ];
    }
}
