<?php

namespace PSLegalcompliance\Form\Type;

use PrestaShopBundle\Form\Admin\Type\TranslatorAwareType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailType extends TranslatorAwareType
{
    public function getBlockPrefix()
    {
        return 'email_checkbox';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'legal_options' => [],
            'mails_available' => [],
            'pdf_attachment' => [],
        ]);

        $resolver->setAllowedTypes('legal_options', ['array']);
        $resolver->setAllowedTypes('mails_available', ['array']);
        $resolver->setAllowedTypes('pdf_attachment', ['array']);
    }


}
