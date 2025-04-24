<?php

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\Type;

use PrestaShop\PrestaShop\Adapter\ServiceLocator;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Onlineshopmodule\PrestaShop\Module\Legalcompliance\Roles;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class CmsType extends TranslatorAwareType
{
    protected $cmsRoleRepository;
    protected $roles;

    public function __construct(
        TranslatorInterface $translator,
        array $locales,
    ) {
        parent::__construct($translator, $locales);

        $this->cmsRoleRepository = ServiceLocator::get('\\PrestaShop\\PrestaShop\\Core\\Foundation\\Database\\EntityManager')->getRepository('CMSRole');
        $this->roles = $this->cmsRoleRepository->findByName(Roles::getAll());
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = $this->getChoices();
        $roleTranslations = $this->getRoleTranslations();

        foreach ($this->roles as $role) {
            $builder
                ->add('CMSROLE_' . $role->id, ChoiceType::class, [
                    'label' => $roleTranslations[$role->name] ?? $role->name,
                    'choices' => $choices,
                    'placeholder' => $this->trans('Select a CMS page', 'Modules.Legalcompliance.Admin'),
                ]);
        }

        $builder
            ->add('AEUC_LINKBLOCK_FOOTER', SwitchType::class, [
                'label' => $this->trans('Display Information block in footer', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('Displays the legal cms-pages links as a separate block in the footer (hook displayFooter). If you switch to no, please keep in mind to add your legal text in your own link blocks on every page.', 'Modules.Legalcompliance.Admin'),
            ])
        ;
    }

    protected function getChoices(): array
    {
        return $this->getRoleChoices($this->roles);
    }

    protected function getRoleChoices(array $roles): array
    {
        $choices = [];
        $roleTranslations = $this->getRoleTranslations();

        foreach ($roles as $role) {
            $name = $role->name;

            if (isset($roleTranslations[$role->name])) {
                $name = $roleTranslations[$role->name];
            }

            $choices[$name] = $role->id_cms;
        }

        return $choices;
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
