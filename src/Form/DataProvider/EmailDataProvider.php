<?php

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\DataProvider;

use AeucCMSRoleEmailEntity;
use AeucEmailEntity;
use PrestaShop\PrestaShop\Adapter\Configuration;
use PrestaShop\PrestaShop\Core\Form\FormDataProviderInterface;
use PrestaShop\PrestaShop\Core\Foundation\Database\EntityManager;
use PrestaShopBundle\Translation\TranslatorInterface;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Roles;
use Symfony\Component\HttpFoundation\RequestStack;

class EmailDataProvider implements FormDataProviderInterface
{
    private $configuration;
    private $translator;
    private $em;
    private $requestStack;

    public function __construct(
        Configuration $configuration,
        TranslatorInterface $translator,
        EntityManager $entity_manager,
        RequestStack $requestStack
    ) {
        $this->configuration = $configuration;
        $this->translator = $translator;
        $this->em = $entity_manager;
        $this->requestStack = $requestStack;
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
        $mailsAvailable = $data['mails_available'];
        $legalOptions = $data['legal_options'];

        $parameters = $this->requestStack->getCurrentRequest()->request->all();

        AeucCMSRoleEmailEntity::truncate();

        foreach ($mailsAvailable as $mailAvailable) {
            foreach ($legalOptions as $legalOption) {
                $idMail = (int) $mailAvailable['id_mail'];
                $idRole = (int) $legalOption['id'];

                if (empty($parameters['attach_' . $idMail . '_' . $idRole])) {
                    continue;
                }

                $assoc_obj = new AeucCMSRoleEmailEntity();
                $assoc_obj->id_mail = $idMail;
                $assoc_obj->id_cms_role = $idRole;
                $assoc_obj->save();
            }
        }

        $this->configuration->set('AEUC_PDF_ATTACHMENT', serialize(array_keys($parameters['pdf_attachment'] ?? [])));

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
