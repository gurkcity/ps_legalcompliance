<?php

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\Type;

use PrestaShop\PrestaShop\Adapter\ServiceLocator;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Roles;
use PrestaShop\PrestaShop\Core\CMS\CMSRepository;
use PrestaShop\PrestaShop\Core\Foundation\Database\EntityManager;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CmsType extends TranslatorAwareType
{
    protected $idShop;
    protected $idLang;
    protected $entityManager;
    protected $cmsRepository;
    protected $cmsRoleRepository;
    protected $roles;

    public function __construct(
        TranslatorInterface $translator,
        array $locales,
        int $idShop,
        int $idLang,
        EntityManager $entityManager
    ) {
        parent::__construct($translator, $locales);

        $this->idShop = $idShop;
        $this->idLang = $idLang;

        $this->entityManager = $entityManager;
        $this->cmsRepository = $this->entityManager->getRepository('CMS');
        $this->cmsRoleRepository = $this->entityManager->getRepository('CMSRole');
        $this->roles = $this->cmsRoleRepository->findByName(Roles::getAll());
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $cmsPages = $this->cmsRepository->i10nFindAll($this->idLang, $this->idShop);
        $choices = array_column($cmsPages, 'id', 'meta_title');
        $roleTranslations = $this->getRoleTranslations();

        foreach ($this->roles as $role) {
            $builder
                ->add('CMSROLE_' . $role->id, ChoiceType::class, [
                    'label' => $roleTranslations[$role->name] ?? $role->name,
                    'choices' => $choices,
                    'placeholder' => $this->trans('-- Select a CMS page --', 'Modules.Legalcompliance.Admin'),
                    'required' => false,
                ]);
        }

        $builder
            ->add('AEUC_LINKBLOCK_FOOTER', SwitchType::class, [
                'label' => $this->trans('Display Information block in footer', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('Displays the legal cms-pages links as a separate block in the footer (hook displayFooter). If you switch to no, please keep in mind to add your legal text in your own link blocks on every page.', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
        ;
    }

    public function getRoleTranslations(): array
    {
        return [
            Roles::NOTICE => $this->trans('Legal notice', 'Modules.Legalcompliance.Admin'),
            Roles::CONDITIONS => $this->trans('Terms of Service (ToS)', 'Modules.Legalcompliance.Admin'),
            Roles::REVOCATION => $this->trans('Revocation terms', 'Modules.Legalcompliance.Admin'),
            Roles::REVOCATION_FORM => $this->trans('Revocation form', 'Modules.Legalcompliance.Admin'),
            Roles::PRIVACY => $this->trans('Privacy', 'Modules.Legalcompliance.Admin'),
            Roles::ENVIRONMENTAL => $this->trans('Environmental notice', 'Modules.Legalcompliance.Admin'),
            Roles::SHIP_PAY => $this->trans('Shipping and payment', 'Modules.Legalcompliance.Admin'),
        ];
    }
}
