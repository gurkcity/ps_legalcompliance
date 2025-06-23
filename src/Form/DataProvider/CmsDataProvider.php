<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\DataProvider;

use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Roles;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;
use PrestaShop\PrestaShop\Core\Foundation\Database\EntityManager;

class CmsDataProvider implements FormDataProviderInterface
{
    private $configuration;
    private $entityManager;
    private $cmsRoleRepository;
    private $roles;

    public function __construct(Configuration $configuration, EntityManager $entity_manager)
    {
        $this->configuration = $configuration;
        $this->entityManager = $entity_manager;
        $this->cmsRoleRepository = $this->entityManager->getRepository('CMSRole');
        $this->roles = Roles::getAll();
    }

    public function getData()
    {
        $cmsRoles = $this->cmsRoleRepository->findByName($this->roles);

        $data = [];

        foreach ($cmsRoles as $role) {
            $data['CMSROLE_' . $role->id] = $role->id_cms;
        }

        $data['AEUC_LINKBLOCK_FOOTER'] = $this->configuration->get('AEUC_LINKBLOCK_FOOTER');

        return $data;
    }

    public function setData(array $data)
    {
        $cmsRoles = $this->cmsRoleRepository->findByName($this->roles);

        foreach ($cmsRoles as $role) {
            if (!isset($data['CMSROLE_' . $role->id])) {
                continue;
            }

            $role->id_cms = (int) $data['CMSROLE_' . $role->id];
            $role->update();
        }

        $this->configuration->set('AEUC_LINKBLOCK_FOOTER', (bool) $data['AEUC_LINKBLOCK_FOOTER']);

        return [];
    }
}
