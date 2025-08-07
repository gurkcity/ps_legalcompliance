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

use PrestaShop\PrestaShop\Core\Form\FormHandlerInterface;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LicenseAdminController extends AdminController
{
    /**
     * @AdminSecurity(
     *     "is_granted('read', request.get('_legacy_controller')) && is_granted('update', request.get('_legacy_controller')) && is_granted('create', request.get('_legacy_controller')) && is_granted('delete', request.get('_legacy_controller'))",
     *     message="Access denied."
     * )
     */
    public function indexAction(
        Request $request,
        #[Autowire(service: 'onlineshopmodule.module.legalcompliance.form.handler.license')]
        FormHandlerInterface $configurationFormHandler,
    ) {
        $this->setLayoutTitle($this->trans('License', [], 'Modules.Legalcompliance.Admin'));

        return $this->processForm(
            $request,
            $configurationFormHandler,
            'ps_legalcompliance_configuration',
            'views/templates/admin/license/license.html.twig'
        );
    }

    protected function processForm(
        Request $request,
        FormHandlerInterface $formHandler,
        string $redirectRoute = 'ps_legalcompliance_configuration',
        string $template = '',
        array $templateParameters = []
    ): Response {
        $form = $formHandler->getForm();
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $saveErrors = $formHandler->save($form->getData());

            if (0 === count($saveErrors)) {
                $this->addFlash('success', $this->trans('Settings saved!', [], 'Modules.Legalcompliance.Admin'));

                return $this->redirectToRoute($redirectRoute);
            } else {
                $this->addFlashErrors($saveErrors);
            }
        }

        $templateParameters = array_merge(
            $templateParameters,
            [
                'form' => $form->createView(),
            ]
        );

        $this->setLayoutTitle($this->trans('License', [], 'Modules.Legalcompliance.Admin'));

        return $this->render($template, $templateParameters);
    }

    public function displayLicenseTextAction(Request $request)
    {
        $response = new Response();
        $response->headers->set('Content-Type', 'text/plain; charset=UTF-8');

        return $this->render('views/templates/admin/license/content.html.twig', [
            'licenseText' => file_get_contents($this->module->getLocalPath() . 'licence.txt'),
        ], $response);
    }
}
