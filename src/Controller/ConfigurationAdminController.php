<?php

namespace PSLegalcompliance\Controller;

use AeucCMSRoleEmailEntity;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\Form\FormHandlerInterface;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PS_Legalcompliance;
use Symfony\Component\HttpFoundation\Request;

class ConfigurationAdminController extends FrameworkBundleAdminController
{
    protected $module;
    protected $configuration;

    public function __construct(
        PS_Legalcompliance $module,
        Configuration $configuration
    ) {
        $this->module = $module;
        $this->configuration = $configuration;
    }

    public function indexAction(Request $request)
    {
        $labelForm = $this->getLabelFormHandler()->getForm();
        $virtualForm = $this->getVirtualFormHandler()->getForm();
        $generalForm = $this->getGeneralFormHandler()->getForm();
        $cmsForm = $this->getCmsFormHandler()->getForm();
        $emailForm = $this->getEmailFormHandler()->getForm();

        return $this->render('@Modules/ps_legalcompliance/views/templates/admin/configuration.html.twig', [
            'labelForm' => $labelForm->createView(),
            'virtualForm' => $virtualForm->createView(),
            'generalForm' => $generalForm->createView(),
            'cmsForm' => $cmsForm->createView(),
            'emailForm' => $emailForm->createView(),
            'emailTemplatesMissing' => $this->module->getNewEmailTemplates(),
        ]);
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
