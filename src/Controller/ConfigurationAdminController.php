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

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\EmailTemplateFinder;
use PrestaShop\PrestaShop\Adapter\ServiceLocator;
use PrestaShop\PrestaShop\Core\Form\FormHandlerInterface;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use PrestaShopBundle\Security\Annotation\ModuleActivated;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @ModuleActivated(moduleName="ps_legalcompliance", redirectRoute="ps_legalcompliance_license")
 */
class ConfigurationAdminController extends AdminController
{
    /**
     * @AdminSecurity(
     *     "is_granted('read', request.get('_legacy_controller')) && is_granted('update', request.get('_legacy_controller')) && is_granted('create', request.get('_legacy_controller')) && is_granted('delete', request.get('_legacy_controller'))",
     *     message="Access denied."
     * )
     */
    public function indexAction(
        Request $request,
        #[Autowire(service: 'pslegalcompliance.form_handler.label')]
        FormHandlerInterface $labelFormHandler,
        #[Autowire(service: 'pslegalcompliance.form_handler.general')]
        FormHandlerInterface $generalFormHandler,
        #[Autowire(service: 'pslegalcompliance.form_handler.cms')]
        FormHandlerInterface $cmsFormHandler
    ): Response {
        if (!$this->module->isLicensed() && !$this->module->isDevMode()) {
            return $this->redirectToRoute('ps_legalcompliance_license');
        }

        $labelForm = $labelFormHandler->getForm();
        $generalForm = $generalFormHandler->getForm();
        $cmsForm = $cmsFormHandler->getForm();

        return $this->render('views/templates/admin/configuration.html.twig', [
            'labelForm' => $labelForm->createView(),
            'generalForm' => $generalForm->createView(),
            'cmsForm' => $cmsForm->createView(),
        ]);
    }

    public function virtualAction(
        Request $request,
        #[Autowire(service: 'pslegalcompliance.form_handler.virtual')]
        FormHandlerInterface $virtualFormHandler
    ): Response {
        if (!$this->module->isLicensed() && !$this->module->isDevMode()) {
            return $this->redirectToRoute('ps_legalcompliance_license');
        }

        $virtualForm = $virtualFormHandler->getForm();

        return $this->render('views/templates/admin/virtual.html.twig', [
            'virtualForm' => $virtualForm->createView(),
        ]);
    }

    public function emailAction(
        Request $request,
        #[Autowire(service: 'pslegalcompliance.form_handler.email')]
        FormHandlerInterface $emailFormHandler
    ): Response {
        if (!$this->module->isLicensed() && !$this->module->isDevMode()) {
            return $this->redirectToRoute('ps_legalcompliance_license');
        }

        $emailForm = $emailFormHandler->getForm();

        return $this->render('views/templates/admin/email.html.twig', [
            'emailForm' => $emailForm->createView(),
            'emailTemplatesMissing' => $this->getNewEmailTemplates(),
        ]);
    }

    public function processLabelFormAction(
        Request $request,
        #[Autowire(service: 'pslegalcompliance.form_handler.label')]
        FormHandlerInterface $formHandler,
    ) {
        return $this->processForm(
            $request,
            $formHandler,
        );
    }

    public function processVirtualFormAction(
        Request $request,
        #[Autowire(service: 'pslegalcompliance.form_handler.virtual')]
        FormHandlerInterface $formHandler,
    ) {
        return $this->processForm(
            $request,
            $formHandler,
        );
    }

    public function processGeneralFormAction(
        Request $request,
        #[Autowire(service: 'pslegalcompliance.form_handler.general')]
        FormHandlerInterface $formHandler,
    ) {
        return $this->processForm(
            $request,
            $formHandler,
        );
    }

    public function processCmsFormAction(
        Request $request,
        #[Autowire(service: 'pslegalcompliance.form_handler.cms')]
        FormHandlerInterface $formHandler,
    ) {
        return $this->processForm(
            $request,
            $formHandler,
        );
    }

    public function processEmailFormAction(
        Request $request,
        #[Autowire(service: 'pslegalcompliance.form_handler.email')]
        FormHandlerInterface $formHandler,
    ) {
        return $this->processForm(
            $request,
            $formHandler,
        );
    }

    protected function saveEmailForm(Request $request, array $data)
    {
        $mailsAvailable = $data['mails_available'];
        $legalOptions = $data['legal_options'];

        $parameters = $request->request->all();

        \AeucCMSRoleEmailEntity::truncate();

        foreach ($mailsAvailable as $mailAvailable) {
            foreach ($legalOptions as $legalOption) {
                $idMail = (int) $mailAvailable['id_mail'];
                $idRole = (int) $legalOption['id'];

                if (empty($parameters['attach_' . $idMail . '_' . $idRole])) {
                    continue;
                }

                $assoc_obj = new \AeucCMSRoleEmailEntity();
                $assoc_obj->id_mail = $idMail;
                $assoc_obj->id_cms_role = $idRole;
                $assoc_obj->save();
            }
        }

        $this->config->set(
            'AEUC_PDF_ATTACHMENT',
            serialize(array_keys($parameters['pdf_attachment'] ?? [])),
            false,
            null,
            false
        );
    }

    protected function getNewEmailTemplates(): array
    {
        $emailTemplateFinder = ServiceLocator::get(EmailTemplateFinder::class);

        return $emailTemplateFinder->findNewEmailTemplates();
    }
}
