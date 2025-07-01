<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Traits;

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Payment\PaymentLogoFactory;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

trait ModulePaymentTrait
{
    public function hookPaymentOptions(array $params): array
    {
        if (
            !$this->isActive()
            || !$this->isPayment()
        ) {
            return [];
        }

        if (
            \Validate::isLoadedObject($params['cart'])
            && !$this->checkCurrency($params['cart'])
        ) {
            return [];
        }

        $paymentOptions = [];

        $newPaymentOption = new PaymentOption();
        $newPaymentOption->setModuleName($this->name);
        $newPaymentOption->setCallToActionText($this->trans('Pay by %module_name%', ['%module_name%' => $this->displayName], 'Modules.Legalcompliance.Shop'));
        $newPaymentOption->setAction($this->context->link->getModuleLink($this->name, 'payment', [], true));
        $newPaymentOption->setInputs([
            ['type' => 'hidden', 'name' => 'submitPayment', 'value' => '1'],
        ]);

        if (is_file($this->getLocalPath() . 'views/templates/front/payment_infos.tpl')) {
            $newPaymentOption->setAdditionalInformation(
                $this->context->smarty->fetch('module:' . $this->name . '/views/templates/front/payment_infos.tpl')
            );
        }

        $paymentLogo = (new PaymentLogoFactory($this))->getPaymentLogo();

        if (
            is_file($paymentLogo->getFilePath())
            && $this->getConfig()->get('SHOW_PAYMENT_LOGO')
        ) {
            $newPaymentOption->setLogo(\Media::getMediaPath($paymentLogo->getFilePathUri()));
        }

        $paymentOptions[] = $newPaymentOption;

        if (method_exists($this, 'parentHookPaymentOptions')) {
            $paymentOptions = $this->parentHookPaymentOptions($params, $paymentOptions);
        }

        return $paymentOptions;
    }

    public function hookDisplayPaymentReturn(array $params): string
    {
        if (
            !$this->isActive()
            || !$this->isPayment()
        ) {
            return '';
        }

        $order = $params['order'];

        if (!\Validate::isLoadedObject($order)) {
            return '';
        }

        if ($order->module != $this->name) {
            return '';
        }

        $currency = new \Currency((int) $order->id_currency);
        $customer = new \Customer((int) $order->id_customer);

        $state = $order->getCurrentState();

        $totalPaid = \Tools::getContextLocale($this->context)->formatPrice(
            $order->total_paid,
            $currency->iso_code
        );

        if (in_array($state, $this->getValidOrderStates())) {
            $this->smarty->assign([
                'status' => 'ok',
                'is_guest' => $customer->is_guest,
                'email' => $customer->email,
                'id_order' => $order->id,
                'reference' => $order->reference,
                'total_paid' => $totalPaid,
                'shop_name' => $this->context->shop->name,
            ]);
        } else {
            $this->smarty->assign([
                'status' => 'error',
                'id_order' => $order->id,
            ]);
        }

        $html = '';

        if (method_exists($this, 'parentHookDisplayPaymentReturn')) {
            $html = $this->parentHookDisplayPaymentReturn($params);
        }

        if (is_file($this->local_path . '/views/templates/hook/payment_return.tpl')) {
            $html = $this->fetch('module:' . $this->name . '/views/templates/hook/payment_return.tpl');
        }

        return $html;
    }

    public function checkCurrency(\Cart $cart): bool
    {
        if (!\Validate::isLoadedObject($cart)) {
            return false;
        }

        $orderCurrency = new \Currency((int) $cart->id_currency);

        /**
         * @var \PaymentModule $this
         */
        // @phpstan-ignore class.notFound
        $moduleCurrencies = $this->getCurrency((int) $cart->id_currency);

        if (is_array($moduleCurrencies)) {
            foreach ($moduleCurrencies as $moduleCurrency) {
                if ($orderCurrency->id == $moduleCurrency['id_currency']) {
                    return true;
                }
            }
        }

        return false;
    }

    public function getValidOrderStates(): array
    {
        $validOrderSates = [
            $this->getConfig()->get('OS'),
            $this->getConfig()->get('OS_NEWORDER'),
            \Configuration::get('PS_OS_OUTOFSTOCK'),
            \Configuration::get('PS_OS_OUTOFSTOCK_UNPAID'),
        ];

        if (method_exists($this, 'parentGetValidOrderStates')) {
            $validOrderSates = $this->parentGetValidOrderStates($validOrderSates);
        }

        return $validOrderSates;
    }
}
