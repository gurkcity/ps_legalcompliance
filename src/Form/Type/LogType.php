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

use Monolog\Logger;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class LogType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $logLevels = Logger::getLevels();

        $builder->add('loglevel', ChoiceType::class, [
            'label' => $this->trans('Log Level', 'Modules.Pslegalcompliance.Admin'),
            'choices' => $logLevels,
            'help' => $this->trans('Determine from which log level the messages should be recorded. Set the log level lower than WARNING only for debug purposes, otherwise the files will be too large.', 'Modules.Pslegalcompliance.Admin'),
        ]);
    }
}
