<?php

/**
 * PS Legalcompliance
 * Module for PrestaShop E-Commerce Software
 *
 * @author    Markus Engel <info@onlineshop-module.de>
 * @copyright Copyright (c) 2025, Onlineshop-Module.de
 * @license   commercial, see licence.txt
 */

namespace Onlineshopmodule\PrestaShop\Module\Legalcompliance\Form\Type;

use PrestaShop\PrestaShop\Core\Foundation\Database\EntityManager;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatableType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class VirtualType extends TranslatorAwareType
{
    private $cmsRepository;
    private $idShop;
    private $idLang;
    private $entityManager;

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
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $cmsPages = $this->cmsRepository->i10nFindAll($this->idLang, $this->idShop);

        $builder
            ->add('AEUC_VP_ACTIVE', SwitchType::class, [
                'label' => $this->trans('Label "Virtual Product"', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('Show a label placed next to the product-tax and links to the virtual products CMS-Infopage', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
            ->add('AEUC_VP_CMS_ID', ChoiceType::class, [
                'label' => $this->trans('Virtual Products CMS-Infopage', 'Modules.Linklist.Admin'),
                'choices' => array_column($cmsPages, 'id', 'meta_title'),
                'placeholder' => $this->trans('-- Select a CMS page --', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
            ->add('AEUC_VP_LABEL_TEXT', TranslatableType::class, [
                'label' => $this->trans('Labeltext "Virtual Product"', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('Text for the label linked to the virtual products CMS-Infopage', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
            ->add('AEUC_LABEL_REVOCATION_VP', SwitchType::class, [
                'label' => $this->trans('Revocation for virtual products', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans('Adds a mandatory checkbox when the cart contains a virtual product. Use it to ensure customers are aware that a virtual product cannot be returned.', 'Modules.Legalcompliance.Admin'),
                'required' => false,
            ])
        ;
    }
}
