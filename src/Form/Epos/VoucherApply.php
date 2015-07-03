<?php

namespace Message\Mothership\Voucher\Form\Epos;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Form for applying a voucher when tendering a transaction in EPOS.
 *
 * @deprecated Moved to EPOS module, use Message\Mothership\Epos\Form\Voucher\VoucherApply instead
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
		$formDisabled = array_key_exists('disabled', $options) && true == $options['disabled'];
		$attributes   = $formDisabled
			? ['class' => 'disabled', 'disabled' => true]
			: [];

		$builder->add('id', 'hidden', [
			'constraints' => new Constraints\NotBlank,
			'attr'        => $attributes,
		]);

		$builder->add('submit', 'submit', [
			'label' => $formDisabled ? 'Voucher already in use' : 'Use',
			'attr'  => $attributes,
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function setDefaultOptions(OptionsResolverInterface $resolver)
	{
		$resolver->setDefaults([
			'data_class' => 'Message\\Mothership\\Voucher\\Voucher',
			'disabled'   => false,
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