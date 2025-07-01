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

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Payment\PaymentLogoFactory;
use PrestaShop\PrestaShop\Core\Form\FormHandlerInterface;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use PrestaShopBundle\Security\Annotation\ModuleActivated;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * @ModuleActivated(moduleName="ps_legalcompliance", redirectRoute="ps_legalcompliance_license")
 */
class PaymentAdminController extends AdminController
{
    /**
     * @AdminSecurity(
     *     "is_granted('read', request.get('_legacy_controller')) && is_granted('update', request.get('_legacy_controller')) && is_granted('create', request.get('_legacy_controller')) && is_granted('delete', request.get('_legacy_controller'))",
     *     message="Access denied."
     * )
     */
    public function indexAction(
        Request $request,
        #[Autowire(service: 'onlineshopmodule.module.legalcompliance.form.handler.payment')]
        FormHandlerInterface $paymentFormHandler,
    ): Response {
        if (!$this->module->isLicensed() && !$this->module->isDevMode()) {
            return $this->redirectToRoute('ps_legalcompliance_license');
        }

        $form = $paymentFormHandler->getForm();
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $formData = $form->getData();

            if (!empty($formData['payment_logo'])) {
                try {
                    $paymentLogo = (new PaymentLogoFactory($this->module))->getPaymentLogo();

                    $savePath = $paymentLogo->getPath();
                    $randomFilename = $paymentLogo->getRandomFilename();
                    $fileEnding = $formData['payment_logo']->guessExtension();

                    $fileName = $randomFilename . '.' . $fileEnding;

                    $formData['payment_logo']->move(
                        $savePath,
                        $fileName
                    );

                    $formData['payment_logo'] = $fileName;
                } catch (FileException $e) {
                    $saveErrors[] = $this->trans('Could not upload logo file', [], 'Modules.Legalcompliance.Admin');
                }
            }

            $saveErrors = $paymentFormHandler->save($formData);

            if (0 === count($saveErrors)) {
                $this->addFlash('success', $this->trans('Settings saved!', [], 'Modules.Legalcompliance.Admin'));

                return $this->redirectToRoute('ps_legalcompliance_payment');
            } else {
                $this->addFlashErrors($saveErrors);
            }
        }

        $templateParameters = [
            'form' => $form->createView(),
        ];

        return $this->render('views/templates/admin/payment.html.twig', $templateParameters);
    }
}
