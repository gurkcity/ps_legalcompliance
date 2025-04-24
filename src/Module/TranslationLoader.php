<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Module;

use Symfony\Contracts\Translation\TranslatorInterface;

class TranslationLoader
{
    private $translator;
    private $translations = [];

    public function __construct(TranslatorInterface $translator, array $translations)
    {
        $this->translator = $translator;
        $this->translations = $translations;
    }

    public function load()
    {
        $locals = $this->getLocales();

        foreach ($locals as $locale) {
            if (empty($this->translations[$locale])) {
                continue;
            }

            $catalogue = $this->translator->getCatalogue($locale);

            if (empty($catalogue)) {
                continue;
            }

            foreach ($this->translations[$locale] as $translation) {
                if ($catalogue->has($translation[0])) {
                    continue;
                }

                $catalogue->set($translation[0], $translation[1], $translation[2]);
            }
        }
    }

    private function getLocales(): array
    {
        $languages = \Language::getLanguages();

        return array_column($languages, 'locale');
    }
}
