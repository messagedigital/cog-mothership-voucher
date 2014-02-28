<?php

namespace Message\Mothership\Voucher\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class VoucherCreate extends AbstractType //AbstractTypeTranslatorAware
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('amount', 'money', [
            'label'       => 'Amount', // get this from translation engine!
            'currency'    => 'GBP',
            'precision'   => 2,
            'constraints' => [
                new Constraints\NotBlank,
                new Constraints\GreaterThan(['value' => 0]),
            ],
            // TODO: add contextual help key
        ]);

        $builder->add('startsAt', 'datetime', [
            'label' => 'Starts at', // get this from trans engine
        ]);

        $builder->add('expiresAt', 'datetime', [
            'label' => 'Expires', // get this from trans engine
        //    ''
        ]);

        // TODO: use form event to generate the ID and set the currency
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'Message\\Mothership\\Voucher\\Voucher',
            'required' => false,
        ]);
    }

    public function getName()
    {
        return 'voucher_create';
    }
}
