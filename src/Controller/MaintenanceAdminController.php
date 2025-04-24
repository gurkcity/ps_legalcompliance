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

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Maintenance\Maintenance;
use PrestaShopBundle\Security\Annotation\AdminSecurity;
use PrestaShopBundle\Security\Annotation\ModuleActivated;
use Symfony\Component\HttpFoundation\Request;

/**
 * @ModuleActivated(moduleName="ps_legalcompliance", redirectRoute="ps_legalcompliance_license")
 */
class MaintenanceAdminController extends AdminController
{
    /**
     * @AdminSecurity(
     *     "is_granted('read', request.get('_legacy_controller')) && is_granted('update', request.get('_legacy_controller')) && is_granted('create', request.get('_legacy_controller')) && is_granted('delete', request.get('_legacy_controller'))",
     *     message="Access denied."
     * )
     */
    public function maintenanceAction(Request $request)
    {
        /**
         * @var Maintenance $maintenance
         */
        $maintenance = $this->get('onlineshopmodule.module.legalcompliance.maintenance');

        return $this->render('views/templates/admin/maintenance/maintenance.html.twig', [
            'hooks' => $maintenance->getHooks(),
            'tabs' => $maintenance->getTabs(),
            'sql' => $maintenance->getSql(),
            'config' => $maintenance->getConfig(),
            'controller' => $maintenance->getController(),
            'orderstates' => $maintenance->getOrderstates(),
            'php_version' => PHP_VERSION,
            'module_version' => $this->module->version,
            'module_gcversion' => $this->module::GC_VERSION,
            'module_gcsubversion' => $this->module::GC_SUBVERSION,
        ]);
    }

    /**
     * @AdminSecurity(
     *     "is_granted('update', request.get('_legacy_controller'))",
     *     message="Access denied."
     * )
     */
    public function maintenanceHooksResetAction(Request $request)
    {
        /**
         * @var Maintenance $maintenance
         */
        $maintenance = $this->get('onlineshopmodule.module.legalcompliance.maintenance');
        $result = $maintenance->resetHooks();

        if (!$result) {
            $this->addFlash('error', $this->trans('Reset failed on some hooks!', 'Modules.Pslegalcompliance.Admin'));
        } else {
            $this->addFlash('success', $this->trans('All hooks have been reset successfully', 'Modules.Pslegalcompliance.Admin'));
        }

        return $this->redirectToRoute('ps_legalcompliance_maintenance');
    }

    /**
     * @AdminSecurity(
     *     "is_granted('update', request.get('_legacy_controller'))",
     *     message="Access denied."
     * )
     */
    public function maintenanceTabsResetAction(Request $request)
    {
        /**
         * @var Maintenance $maintenance
         */
        $maintenance = $this->get('onlineshopmodule.module.legalcompliance.maintenance');
        $result = $maintenance->resetTabs();

        if (!$result) {
            $this->addFlash('error', $this->trans('Reset failed on some tabs!', 'Modules.Pslegalcompliance.Admin'));
        } else {
            $this->addFlash('success', $this->trans('All tabs have been reset successfully', 'Modules.Pslegalcompliance.Admin'));
        }

        return $this->redirectToRoute('ps_legalcompliance_maintenance');
    }

    /**
     * @AdminSecurity(
     *     "is_granted('update', request.get('_legacy_controller'))",
     *     message="Access denied."
     * )
     */
    public function maintenanceSqlResetAction(Request $request)
    {
        /**
         * @var Maintenance $maintenance
         */
        $maintenance = $this->get('onlineshopmodule.module.legalcompliance.maintenance');

        try {
            $maintenance->resetSql();

            $this->addFlash('success', $this->trans('SQL queries has been executed successfully', 'Modules.Pslegalcompliance.Admin'));
        } catch (\Throwable $e) {
            $this->addFlash('error', $this->trans('Reset SQL failed! %error%', 'Modules.Pslegalcompliance.Admin', ['%error%' => $e->getMessage()]));
        }

        return $this->redirectToRoute('ps_legalcompliance_maintenance');
    }

    /**
     * @AdminSecurity(
     *     "is_granted('update', request.get('_legacy_controller'))",
     *     message="Access denied."
     * )
     */
    public function maintenanceConfigResetAction(Request $request)
    {
        /**
         * @var Maintenance $maintenance
         */
        $maintenance = $this->get('onlineshopmodule.module.legalcompliance.maintenance');
        $result = $maintenance->resetConfig();

        if (!$result) {
            $this->addFlash('error', $this->trans('Install missing configuration failed!', 'Modules.Pslegalcompliance.Admin'));
        } else {
            $this->addFlash('success', $this->trans('Missing configuration installed successfully', 'Modules.Pslegalcompliance.Admin'));
        }

        return $this->redirectToRoute('ps_legalcompliance_maintenance');
    }

    /**
     * @AdminSecurity(
     *     "is_granted('update', request.get('_legacy_controller'))",
     *     message="Access denied."
     * )
     */
    public function maintenanceControllerResetAction(Request $request)
    {
        /**
         * @var Maintenance $maintenance
         */
        $maintenance = $this->get('onlineshopmodule.module.legalcompliance.maintenance');
        $result = $maintenance->resetController();

        if (!$result) {
            $this->addFlash('error', $this->trans('Reset of controller failed!', 'Modules.Pslegalcompliance.Admin'));
        } else {
            $this->addFlash('success', $this->trans('Controller reseted successfully', 'Modules.Pslegalcompliance.Admin'));
        }

        return $this->redirectToRoute('ps_legalcompliance_maintenance');
    }

    /**
     * @AdminSecurity(
     *     "is_granted('update', request.get('_legacy_controller'))",
     *     message="Access denied."
     * )
     */
    public function maintenanceOrderstatesResetAction(Request $request)
    {
        /**
         * @var Maintenance $maintenance
         */
        $maintenance = $this->get('onlineshopmodule.module.legalcompliance.maintenance');
        $result = $maintenance->resetOrderstates();

        if (!$result) {
            $this->addFlash('error', $this->trans('Reset of order states failed!', 'Modules.Pslegalcompliance.Admin'));
        } else {
            $this->addFlash('success', $this->trans('Order states reseted successfully', 'Modules.Pslegalcompliance.Admin'));
        }

        return $this->redirectToRoute('ps_legalcompliance_maintenance');
    }
}
