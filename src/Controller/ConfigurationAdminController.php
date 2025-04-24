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

use AeucCMSRoleEmailEntity;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\Type\ConfigurationType;
use PrestaShop\PrestaShop\Core\Form\FormHandlerInterface;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use PrestaShopBundle\Security\Annotation\ModuleActivated;
use Symfony\Component\HttpFoundation\Request;

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
    public function indexAction(Request $request)
    {
        if (!$this->module->isLicensed()) {
            return $this->redirectToRoute('ps_legalcompliance_license');
        }

        $configurationForm = $this->createForm(
            ConfigurationType::class,
            $this->getConfigurationData()
        );

        $configurationForm->handleRequest($request);

        if (
            $configurationForm->isSubmitted()
            && $configurationForm->isValid()
        ) {
            $formData = $configurationForm->getData();

            if ($this->handleForm($formData)) {
                $this->addFlash('success', $this->trans('Settings saved!', 'Modules.Pslegalcompliance.Admin'));

                $this->redirectToRoute('ps_legalcompliance_configuration');
            }
        }

        $paymentForm = $this->processAndGetPaymentForm($request);

        $labelForm = $this->getLabelFormHandler()->getForm();
        $virtualForm = $this->getVirtualFormHandler()->getForm();
        $generalForm = $this->getGeneralFormHandler()->getForm();
        $cmsForm = $this->getCmsFormHandler()->getForm();
        $emailForm = $this->getEmailFormHandler()->getForm();

        return $this->render('views/templates/admin/configuration.html.twig', [
            'configurationForm' => $configurationForm->createView(),
            'labelForm' => $labelForm->createView(),
            'virtualForm' => $virtualForm->createView(),
            'generalForm' => $generalForm->createView(),
            'cmsForm' => $cmsForm->createView(),
            'emailForm' => $emailForm->createView(),
            'emailTemplatesMissing' => $this->module->getNewEmailTemplates(),
            'paymentForm' => $paymentForm !== null ? $paymentForm->createView() : null,
        ]);
    }

    private function handleForm($datas): bool
    {
        $result = true;

        /* process form here */
        // $this->updateConfiguration('CONFIG_VALUE', 'config_value', (int) $datas['config_value']);

        return $result;
    }

    private function getConfigurationData(): array
    {
        return [
            // 'config_value' => (int) $this->config->get('CONFIG_VALUE'),
        ];
    }

    public function processLabelFormAction(Request $request)
    {
        return $this->processForm(
            $request,
            $this->getLabelFormHandler(),
            'Label'
        );
    }

    public function processVirtualFormAction(Request $request)
    {
        return $this->processForm(
            $request,
            $this->getVirtualFormHandler(),
            'Virtual'
        );
    }

    public function processGeneralFormAction(Request $request)
    {
        return $this->processForm(
            $request,
            $this->getGeneralFormHandler(),
            'General'
        );
    }

    public function processCmsFormAction(Request $request)
    {
        return $this->processForm(
            $request,
            $this->getCmsFormHandler(),
            'Cms'
        );
    }

    public function processEmailFormAction(Request $request)
    {
        return $this->processForm(
            $request,
            $this->getEmailFormHandler(),
            'Email'
        );
    }

    protected function processForm(Request $request, FormHandlerInterface $formHandler, string $hookName)
    {
        $this->dispatchHook(
            'actionAdminLegalcomplianceControllerPostProcess' . $hookName . 'Before',
            ['controller' => $this]
        );

        $this->dispatchHook('actionAdminLegalcomplianceControllerPostProcessBefore', ['controller' => $this]);

        $form = $formHandler->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $data = $form->getData();

            if ($form->getName() == 'email') {
                $this->saveEmailForm($request, $data);
            } else {
                $errors = $formHandler->save($data);
            }

            if (!empty($errors)) {
                $this->flashErrors($errors);

                return $this->redirectToRoute('legalcompliance');
            }

            $this->addFlash('success', $this->trans('Update successful', 'Admin.Notifications.Success'));
        }

        return $this->redirectToRoute('legalcompliance');
    }

    protected function saveEmailForm(Request $request, array $data)
    {
        $mailsAvailable = $data['mails_available'];
        $legalOptions = $data['legal_options'];

        $parameters = $request->request->all();

        AeucCMSRoleEmailEntity::truncate();

        foreach ($mailsAvailable as $mailAvailable) {
            foreach ($legalOptions as $legalOption) {
                $idMail = (int) $mailAvailable['id_mail'];
                $idRole = (int) $legalOption['id'];

                if (empty($parameters['attach_' . $idMail . '_' . $idRole])) {
                    continue;
                }

                $assoc_obj = new AeucCMSRoleEmailEntity();
                $assoc_obj->id_mail = $idMail;
                $assoc_obj->id_cms_role = $idRole;
                $assoc_obj->save();
            }
        }

        $this->configuration->set('AEUC_PDF_ATTACHMENT', serialize(array_keys($parameters['pdf_attachment'] ?? [])));
    }

    protected function getLabelFormHandler(): FormHandlerInterface
    {
        return $this->get('pslegalcompliance.form_handler.label');
    }

    protected function getVirtualFormHandler(): FormHandlerInterface
    {
        return $this->get('pslegalcompliance.form_handler.virtual');
    }

    protected function getGeneralFormHandler(): FormHandlerInterface
    {
        return $this->get('pslegalcompliance.form_handler.general');
    }

    protected function getCmsFormHandler(): FormHandlerInterface
    {
        return $this->get('pslegalcompliance.form_handler.cms');
    }

    protected function getEmailFormHandler(): FormHandlerInterface
    {
        return $this->get('pslegalcompliance.form_handler.email');
    }
}
