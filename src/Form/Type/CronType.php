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

use PrestaShop\PrestaShop\Core\ConstraintValidator\Constraints\PositiveOrZero;
use PrestaShopBundle\Form\Admin\Type\SwitchType;
use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;

class CronType extends TranslatorAwareType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('maintenance', SwitchType::class, [
            'label' => $this->trans('Cron run on maintenance', 'Modules.Legalcompliance.Admin'),
            'help' => $this->trans('Run cronjobs even maintenance mode is on.', 'Modules.Legalcompliance.Admin'),
        ]);

        if (!empty($options['data']['using_queue'])) {
            $builder->add('rows_per_run', NumberType::class, [
                'label' => $this->trans('Rows per run', 'Modules.Legalcompliance.Admin'),
                'help' => $this->trans(
                    'How many lines should be processed per cronjob call? Please note the average runtime of the cron jobs in relation to the execution interval.',
                    'Modules.Legalcompliance.Admin'
                ),
                'constraints' => [
                    new PositiveOrZero([
                        'message' => $this->trans('Please select a number greater than zero.', 'Modules.Legalcompliance.Admin'),
                    ]),
                ],
            ]);
        }
    }
}
