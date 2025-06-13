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

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Log\LogLevel;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Log\LogRepository;
use PrestaShop\PrestaShop\Core\Form\FormHandlerInterface;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use PrestaShopBundle\Security\Annotation\ModuleActivated;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @ModuleActivated(moduleName="ps_legalcompliance", redirectRoute="ps_legalcompliance_license")
 */
class LogAdminController extends AdminController
{
    /**
     * @AdminSecurity(
     *     "is_granted('read', request.get('_legacy_controller')) && is_granted('update', request.get('_legacy_controller')) && is_granted('create', request.get('_legacy_controller')) && is_granted('delete', request.get('_legacy_controller'))",
     *     message="Access denied."
     * )
     */
    public function indexAction(
        Request $request,
        #[Autowire(service: 'onlineshopmodule.module.legalcompliance.form.handler.log')]
        FormHandlerInterface $configurationFormHandler,
        LogLevel $logLevel,
        LogRepository $logRepository
    ) {
        return $this->processForm(
            $request,
            $configurationFormHandler,
            'ps_legalcompliance_logs',
            'views/templates/admin/log/logs.html.twig',
            [
                'logFiles' => $logRepository->getExistingFiles(),
                'logLevel' => $logLevel->get(),
            ]
        );
    }

    /**
     * @AdminSecurity(
     *     "is_granted('read', request.get('_legacy_controller'))",
     *     message="Access denied."
     * )
     */
    public function viewAction(
        $filename,
        Request $request,
        LogRepository $logRepository
    ) {
        $log_content = $logRepository->getContent($filename);

        return new Response(nl2br(htmlspecialchars($log_content)), 200, ['Content-Type' => 'text/html']);
    }

    /**
     * @AdminSecurity(
     *     "is_granted('delete', request.get('_legacy_controller'))",
     *     message="Access denied."
     * )
     */
    public function clearAction(Request $request, LogRepository $logRepository)
    {
        $result = $logRepository->clear();

        if (!$result) {
            $this->addFlash('error', $this->trans('Log files could not be deleted!', [], 'Modules.Pslegalcompliance.Admin'));
        } else {
            $this->addFlash('success', $this->trans('Log files have been cleared', [], 'Modules.Pslegalcompliance.Admin'));
        }

        return $this->redirectToRoute('ps_legalcompliance_logs');
    }

    /**
     * @AdminSecurity(
     *     "is_granted('delete', request.get('_legacy_controller'))",
     *     message="Access denied."
     * )
     */
    public function deleteAction($filename, Request $request, LogRepository $logRepository)
    {
        $result = $logRepository->delete($filename);

        if (!$result) {
            $this->addFlash('error', $this->trans('Log file could not be deleted!', [], 'Modules.Pslegalcompliance.Admin'));
        } else {
            $this->addFlash('success', $this->trans('Log fils have been deleted', [], 'Modules.Pslegalcompliance.Admin'));
        }

        return $this->redirectToRoute('ps_legalcompliance_logs');
    }
}
