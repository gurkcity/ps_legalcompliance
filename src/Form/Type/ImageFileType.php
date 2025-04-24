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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ImageFileType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'image_file' => '',
            'image_width' => 0,
            'image_height' => 0,
        ]);

        $resolver->setAllowedTypes('image_file', ['null', 'string']);
        $resolver->setAllowedTypes('image_width', 'int');
        $resolver->setAllowedTypes('image_height', 'int');
    }

    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['image_file'] = $options['image_file'];
        $view->vars['image_width'] = $options['image_width'];
        $view->vars['image_height'] = $options['image_height'];
    }

    public function getParent(): string
    {
        return FileType::class;
    }

    public function getBlockPrefix()
    {
        return 'image_file';
    }
}
