<?php

namespace Message\Mothership\Voucher\Form\Epos;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Form for applying a voucher when tendering a transaction in EPOS.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class VoucherApply extends AbstractType
{
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('id', 'hidden', [
			'constraints' => new Constraints\NotBlank,
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'Message\\Mothership\\Voucher\\Voucher',
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return 'epos_tender_voucher_apply';
	}
}