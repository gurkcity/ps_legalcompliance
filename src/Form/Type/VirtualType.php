<?php

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\Type;

use PrestaShop\PrestaShop\Adapter\ServiceLocator;
use PrestaShopBundle\Form\Admin\Type\MultistoreConfigurationType;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use Symfony\Component\Form\FormBuilderInterface;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\Translation\TranslatorInterface;

class VirtualType extends TranslatorAwareType
{
    private $cmsRepository;
    private $idShop;
    private $idLang;

    public function __construct(
        TranslatorInterface $translator,
        array $locales,
        int $idShop,
        int $idLang
    ) {
        parent::__construct($translator, $locales);

        $this->idShop = $idShop;
        $this->idLang = $idLang;

        $this->cmsRepository = ServiceLocator::get('\\PrestaShop\\PrestaShop\\Core\\Foundation\\Database\\EntityManager')->getRepository('CMS');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $cmsPages = $this->cmsRepository->i10nFindAll($this->idLang, $this->idShop);

        $builder
            ->add('AEUC_VP_ACTIVE', SwitchType::class, [
                'label' => $this->trans('Label "Virtual Product"', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('Show a label placed next to the product-tax and links to the virtual products CMS-Infopage', 'Modules.Legalcompliance.Admin'),
            ])
            ->add('AEUC_VP_CMS_ID', ChoiceType::class, [
                'label' => $this->trans('Virtual Products CMS-Infopage', 'Modules.Linklist.Admin'),
                'choices' => array_map(function ($item) {
                    return [$item->meta_title => $item->id];
                }, $cmsPages),
            ])
            ->add('AEUC_VP_LABEL_TEXT', TranslatableType::class, [
                'label' => $this->trans('Labeltext "Virtual Product"', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('Text for the label linked to the virtual products CMS-Infopage', 'Modules.Legalcompliance.Admin'),
            ])
            ->add('AEUC_LABEL_REVOCATION_VP', SwitchType::class, [
                'label' => $this->trans('Revocation for virtual products', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('Adds a mandatory checkbox when the cart contains a virtual product. Use it to ensure customers are aware that a virtual product cannot be returned.', 'Modules.Legalcompliance.Admin'),
            ])
        ;
    }
}
