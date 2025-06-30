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
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends PrestaShopAdminController
{
    protected $config;
    protected $logger;
    protected $module;

    public function __construct(
        \PS_Legalcompliance $module
    ) {
        $this->module = $module;
        $this->config = $module->getConfig();
        $this->logger = $module->getLogger();
    }

    protected function processForm(
        Request $request,
        FormHandlerInterface $formHandler,
        string $redirectRoute = 'ps_legalcompliance_configuration',
        string $template = '',
        array $templateParameters = []
    ): Response {
        if (!$this->module->isLicensed()) {
            return $this->redirectToRoute('ps_legalcompliance_license');
        }

        $form = $formHandler->getForm();
        $form->handleRequest($request);

        if (
            $form->isSubmitted()
            && $form->isValid()
        ) {
            $saveErrors = $formHandler->save($form->getData());

            if (0 === count($saveErrors)) {
                $this->addFlash('success', $this->trans('Settings saved!', [], 'Modules.Pslegalcompliance.Admin'));

                return $this->redirectToRoute($redirectRoute);
            } else {
                $this->addFlashErrors($saveErrors);
            }
        }

        if (empty($template)) {
            return $this->redirectToRoute($redirectRoute);
        }

        $templateParameters = array_merge(
            $templateParameters,
            [
                'form' => $form->createView(),
            ]
        );

        return $this->render($template, $templateParameters);
    }

    protected function getToolbarButtons(): array
    {
        $languageContext = $this->getLanguageContext();

        return [
            'hooks' => [
                'href' => $this->generateUrl('admin_modules_positions', ['show_modules' => (int) $this->module->id]),
                'desc' => $this->trans('Manage hooks', [], 'Modules.Pslegalcompliance.Admin'),
                'icon' => 'anchor',
                'class' => 'btn-default',
            ],
            'translation' => [
                'href' => $this->generateUrl(
                    'admin_international_translation_overview',
                    [
                        'lang' => $languageContext->getIsocode(),
                        'type' => 'modules',
                        'locale' => $languageContext->getLocale(),
                        'selected' => $this->module->name
                    ]
                ),
                'desc' => $this->trans('Translate', [], 'Modules.Pslegalcompliance.Admin'),
                'icon' => 'flag',
                'class' => 'btn-default',
            ],
        ];
    }

    protected function getHeaderVars(): array
    {
        return [
            'module' => [
                'name' => $this->module->name,
                'display_name' => $this->module->displayName,
                'display_name_pre' => $this->module->displayNamePre,
                'display_name_post' => $this->module->displayNamePost,
                'description' => $this->module->description,
                'description_full' => $this->module->description_full,
                'version' => $this->module->version,
                'author' => $this->module->author,
                'gc_module_version' => $this->module->getGCModuleVersion(),
                'logo' => $this->module->getPathUri() . 'logo.png',
                'license_key' => $this->module->getLicenseKey(),
                'path' => $this->module->getPathUri(),
            ],
            'layoutHeaderToolbarBtn' => $this->getToolbarButtons(),
            'help_link' => false,
        ];
    }

    protected function render(
        string $view,
        array $parameters = [],
        ?Response $response = null
    ): Response {
        $parameters = array_merge(
            $this->getHeaderVars(),
            $parameters
        );

        if (!strpos($view, '@Modules')) {
            $view = '@Modules/' . $this->module->name . '/' . $view;
        }

        return parent::render($view, $parameters, $response);
    }

    protected function isAjax(Request $request): bool
    {
        if (
            $request->isXmlHttpRequest()
            || $request->query->has('ajax')
            || $request->request->has('ajax')
        ) {
            return true;
        }

        return false;
    }
}
