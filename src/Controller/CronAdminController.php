<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Controller;

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Cron\CronPresenter;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Cron\CronQueueRepository;
use PrestaShop\PrestaShop\Core\Form\FormHandlerInterface;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use PrestaShopBundle\Security\Annotation\ModuleActivated;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;

/**
 * @ModuleActivated(moduleName="ps_legalcompliance", redirectRoute="ps_legalcompliance_license")
 */
class CronAdminController extends AdminController
{
    /**
     * @AdminSecurity(
     *     "is_granted('read', request.get('_legacy_controller')) && is_granted('update', request.get('_legacy_controller')) && is_granted('create', request.get('_legacy_controller')) && is_granted('delete', request.get('_legacy_controller'))",
     *     message="Access denied."
     * )
     */
    public function cronAction(
        Request $request,
        CronPresenter $cronPresenter,
        CronQueueRepository $cronQueueRepository,
        #[Autowire(service: 'onlineshopmodule.module.legalcompliance.form.handler.cron')]
        FormHandlerInterface $cronFormHandler,
    ) {
        $usingQueue = $this->module->getSettings()->isCronUsingQueue();
        $cronSettings = $this->module->getSettings()->getCron();

        return $this->processForm(
            $request,
            $cronFormHandler,
            'ps_legalcompliance_cron',
            'views/templates/admin/cron/cron.html.twig',
            [
                'cronJobs' => $cronPresenter->present($cronSettings),
                'usingQueue' => $usingQueue,
                'cronQueue' => $usingQueue ? $cronQueueRepository->getStats() : [],
            ]
        );
    }
}
