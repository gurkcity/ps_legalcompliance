<?php

namespace PSLegalcompliance\Form\DataProvider;

use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Adapter\ServiceLocator;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;
use PSLegalcompliance\Roles;

class CmsDataProvider implements FormDataProviderInterface
{
    private $configuration;
    private $cmsRoleRepository;
    private $roles;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->cmsRoleRepository = ServiceLocator::get('\\PrestaShop\\PrestaShop\\Core\\Foundation\\Database\\EntityManager')->getRepository('CMSRole');
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
