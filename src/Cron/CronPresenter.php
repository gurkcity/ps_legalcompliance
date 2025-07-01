<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Cron;

use PrestaShop\PrestaShop\Adapter\LegacyContext;
use PrestaShop\PrestaShop\Adapter\Presenter\PresenterInterface;
use PrestaShop\PrestaShop\Core\Module\Legacy\ModuleInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CronPresenter implements PresenterInterface
{
    private $translator;

    private $module;

    private $context;

    public function __construct(
        TranslatorInterface $translator,
        ModuleInterface $module,
        LegacyContext $legacyContext
    ) {
        $this->translator = $translator;
        $this->module = $module;
        $this->context = $legacyContext->getContext();
    }

    public function present($cron_settings)
    {
        foreach ($cron_settings as $methodName => $cron) {
            $cron_settings[$methodName] = $this->decorateCron($cron, $methodName);
        }

        return $cron_settings;
    }

    protected function decorateCron(array $cron, string $methodName): array
    {
        $cron['title'] = $cron['title'] ?? '';
        $cron['method'] = $methodName;
        $cron['description'] = $cron['description'] ?? $this->translator->trans('Take this cron url to prepare the cronjob for the action \'%action_name%\'', ['%action_name%' => $cron['title']], 'Modules.Legalcompliance.Admin');
        $cron['params'] = $cron['params'] ?? [];

        $shopUrl = \Tools::getShopProtocol() . $this->context->shop->domain . $this->context->shop->getBaseURI();
        $cron['url_wget'] = $shopUrl . 'index.php?fc=module&module=' . $this->module->name . '&controller=cron&secure_key=' . $this->module->secureKey . '&action=' . $cron['method'];
        $cron['url_php'] = _PS_ROOT_DIR_ . '/index.php \'fc=module&module=' . $this->module->name . '&controller=cron&secure_key=' . $this->module->secureKey . '&action=' . $cron['method'] . '\'';

        foreach ($cron['params'] as $key => $params) {
            if (empty($params['name']) || empty($params['values'])) {
                unset($cron['params'][$key]);
                continue;
            }

            $params['description'] = $params['description'] ?? '';
            $params['title'] = $params['title'] ?? $params['name'];
        }

        return $cron;
    }
}
