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

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\Type\LicenseType;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use Symfony\Component\HttpFoundation\Request;

class LicenseAdminController extends AdminController
{
    /**
     * @AdminSecurity(
     *     "is_granted('read', request.get('_legacy_controller')) && is_granted('update', request.get('_legacy_controller')) && is_granted('create', request.get('_legacy_controller')) && is_granted('delete', request.get('_legacy_controller'))",
     *     message="Access denied."
     * )
     */
    public function indexAction(Request $request)
    {
        $form = $this->createForm(LicenseType::class, [
            'license' => $this->config->getGlobal('LICENSE'),
            'privacy' => (bool) $this->config->getGlobal('PRIVACY'),
        ]);

        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $formData = $form->getData();
            $license = trim($formData['license']);

            $this->config->setGlobal('LICENSE', $license);
            $this->config->setGlobal('PRIVACY', true);

            $this->module->registerLicense($license);

            $this->module->logger->license->info(sprintf('New license registered %s', $license));

            $this->addFlash('success', $this->trans('License accepted. Now You can use the full features of this module.', 'Modules.Pslegalcompliance.Admin'));

            return $this->redirectToRoute('ps_legalcompliance_configuration');
        }

        return $this->render('views/templates/admin/license/license.html.twig', [
            'licenseForm' => $form->createView(),
        ]);
    }
}
