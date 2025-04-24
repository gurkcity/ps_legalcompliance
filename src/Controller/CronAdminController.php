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

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\Type\CronType;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use PrestaShopBundle\Security\Annotation\ModuleActivated;
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
    public function cronAction(Request $request)
    {
        $usingQueue = $this->module->getSettings()->isCronUsingQueue();

        $cronForm = $this->createForm(CronType::class, [
            'maintenance' => (bool) $this->config->getGlobal('CRON_MAINTENANCE'),
            'rows_per_run' => (int) $this->config->getGlobal('CRON_ROWS_PER_RUN'),
            'using_queue' => $usingQueue,
        ]);

        $cronForm->handleRequest($request);

        if (
            $cronForm->isSubmitted()
            && $cronForm->isValid()
        ) {
            $formData = $cronForm->getData();

            $this->config->setGlobal('CRON_MAINTENANCE', $formData['maintenance']);

            if ($usingQueue) {
                $this->config->setGlobal('CRON_ROWS_PER_RUN', $formData['rows_per_run']);
            }

            $this->addFlash('success', $this->trans('Settings saved!', 'Modules.Pslegalcompliance.Admin'));

            return $this->redirectToRoute('ps_legalcompliance_cron');
        }

        $cronPresenter = $this->get('onlineshopmodule.module.legalcompliance.cronpresenter');
        $cronSettings = $this->module->getSettings()->getCron();

        $presentedCron = $cronPresenter->present($cronSettings);

        $cronQueueRepository = $this->get('onlineshopmodule.module.legalcompliance.cronqueuerepository');

        return $this->render('views/templates/admin/cron/cron.html.twig', [
            'cronForm' => $cronForm->createView(),
            'cronJobs' => $presentedCron,
            'usingQueue' => $usingQueue,
            'cronQueue' => $usingQueue ? $cronQueueRepository->getStats() : [],
        ]);
    }
}
