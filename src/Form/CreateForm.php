<?php

namespace Message\Mothership\Voucher\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;

class CreateForm extends AbstractType
{
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		// Commerce's currency select field
		$builder->add('currency', 'currency_select', [
				'constraints' => [
					new Constraints\NotBlank,
				],
			]);

		$builder->add('amount', 'number', [
				'label' => 'ms.voucher.amount.label',
				'precision' => 2,
				'attr'     => [
					'data-help-key' => 'ms.voucher.amount.help',
				],
				'constraints' => [
					new Constraints\NotBlank,
				],
			]);

		$builder->add('startsAt', 'datetime', [
			'label' => 'ms.voucher.starts.label',
			'attr' => [
				'data-help-key' => 'ms.voucher.starts.help',
			],
		]);

		$builder->add('expiry', 'datetime', [
			'label' => 'ms.voucher.expiry.label',
			'attr' => [
				'data-help-key' => 'ms.voucher.expiry.help',
			],
		]);
	}

	public function getName()
	{
		return 'create_voucher';
	}
}