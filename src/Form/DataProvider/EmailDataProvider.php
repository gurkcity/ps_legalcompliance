<?php

namespace PSLegalcompliance\Form\DataProvider;

use AeucCMSRoleEmailEntity;
use AeucEmailEntity;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;
use PrestaShop\PrestaShop\Core\Foundation\Database\EntityManager;
use PrestaShopBundle\Translation\TranslatorInterface;
use PSLegalcompliance\Roles;

class EmailDataProvider implements FormDataProviderInterface
{
    private $configuration;
    private $translator;
    private $em;

    public function __construct(
        Configuration $configuration,
        TranslatorInterface $translator,
        EntityManager $entity_manager
    ) {
        $this->configuration = $configuration;
        $this->translator = $translator;
        $this->em = $entity_manager;
    }

    public function getData()
    {
        $cms_roles_aeuc = Roles::getTranslated($this->translator);

        $cms_role_repository = $this->em->getRepository('CMSRole');
        $cms_roles_associated = $cms_role_repository->getCMSRolesAssociated();
        $legal_options = [];
        $cleaned_mails_names = [];

        foreach ($cms_roles_associated as $role) {
            $list_id_mail_assoc = AeucCMSRoleEmailEntity::getIdEmailFromCMSRoleId((int) $role->id);
            $clean_list = [];

            foreach ($list_id_mail_assoc as $list_id_mail_assoc) {
                $clean_list[] = $list_id_mail_assoc['id_mail'];
            }

            $legal_options[$role->name] = [
                'name' => $cms_roles_aeuc[$role->name],
                'id' => $role->id,
                'list_id_mail_assoc' => $clean_list,
            ];
        }

        foreach (AeucEmailEntity::getAll() as $email) {
            $cleaned_mails_names[] = $email;
        }

        return [
            'legal_options' => $legal_options,
            'mails_available' => $cleaned_mails_names,
            'pdf_attachment' => $this->getPDFAttachmentOptions(),
        ];
    }

    public function setData(array $data)
    {
        return [];
    }

    private function getPDFAttachmentOptions()
    {
        $pdf_attachment = unserialize($this->configuration->get('AEUC_PDF_ATTACHMENT'));

        if (!is_array($pdf_attachment)) {
            $pdf_attachment = [];
        }

        return $pdf_attachment;
    }
}
