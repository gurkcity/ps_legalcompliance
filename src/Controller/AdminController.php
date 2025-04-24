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

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\Type\PaymentType;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Payment\PaymentLogoFactory;
use PrestaShop\PrestaShop\Adapter\Shop\Context;
use PrestaShop\PrestaShop\Core\Domain\Shop\ValueObject\ShopConstraint;
use PrestaShop\PrestaShop\Core\Feature\FeatureInterface;
use PrestaShopBundle\Controller\Admin\FrameworkBundleAdminController;
use PrestaShopBundle\Service\Form\MultistoreCheckboxEnabler;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminController extends FrameworkBundleAdminController
{
    protected $config;
    protected $logger;
    protected $module;
    protected $shopContext;
    protected $multistoreFeature;

    public function __construct(
        \PS_Legalcompliance $module,
        Context $shopContext,
        FeatureInterface $multistoreFeature
    ) {
        $this->module = $module;
        $this->config = $module->getConfig();
        $this->logger = $module->getLogger();

        $this->shopContext = $shopContext;
        $this->multistoreFeature = $multistoreFeature;
    }

    protected function processAndGetPaymentForm(Request $request): ?FormInterface
    {
        if (!$this->module->isPayment()) {
            return null;
        }

        $paymentForm = $this->createForm(PaymentType::class, [
            'os_neworder' => $this->config->get('OS_NEWORDER'),
            'awaiting_payment' => $this->config->get('AWAITING_PAYMENT'),
            'os' => $this->config->get('OS'),
            'show_payment_logo' => $this->config->get('SHOW_PAYMENT_LOGO'),
        ]);

        $paymentForm->handleRequest($request);

        if (
            $paymentForm->isSubmitted()
            && $paymentForm->isValid()
        ) {
            $formData = $paymentForm->getData();

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

                    $this->updateConfiguration('PAYMENT_LOGO', 'payment_logo', $fileName);
                } catch (FileException $e) {
                    $this->addFlash('error', $this->trans('Could not upload logo file', 'Modules.Pslegalcompliance.Admin'));
                }
            }

            $this->updateConfiguration('OS_NEWORDER', 'os_neworder', (int) $formData['os_neworder']);
            $this->updateConfiguration('AWAITING_PAYMENT', 'awaiting_payment', (int) $formData['awaiting_payment']);
            $this->updateConfiguration('OS', 'os', (int) $formData['os']);
            $this->updateConfiguration('SHOW_PAYMENT_LOGO', 'show_payment_logo', (int) $formData['show_payment_logo']);

            $this->addFlash('success', $this->trans('Payment settings saved.', 'Modules.Pslegalcompliance.Admin'));
        }

        return $paymentForm;
    }

    protected function getToolbarButtons(): array
    {
        return [
            'hooks' => [
                'href' => $this->generateUrl('admin_modules_positions', ['show_modules' => (int) $this->module->id]),
                'desc' => $this->trans('Manage hooks', 'Modules.Pslegalcompliance.Admin'),
                'icon' => 'anchor',
                'class' => 'btn-default',
            ],
            'translation' => [
                'href' => $this->generateUrl('admin_international_translations_show_settings'),
                'desc' => $this->trans('Translate', 'Modules.Pslegalcompliance.Admin'),
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

    protected function updateConfiguration(
        string $key,
        string $fieldName,
        $value,
        ?ShopConstraint $shopConstraint = null,
        array $options = []
    ) {
        $multistoreFieldPrefix = MultistoreCheckboxEnabler::MULTISTORE_FIELD_PREFIX;

        $configPrefix = isset($options['prefix']) ? $options['prefix'] : true;

        if (
            $this->multistoreFeature->isUsed()
            && !$this->shopContext->isAllShopContext()
            && !isset($value[$multistoreFieldPrefix . $fieldName])
        ) {
            $this->config->deleteFromContext($key, $shopConstraint, $configPrefix);
        } else {
            $this->config->set($key, $value, $options['html'] ?? false, $shopConstraint, $configPrefix);
        }
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
