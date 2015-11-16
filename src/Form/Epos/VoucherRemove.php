<?php

namespace Message\Mothership\Voucher\Form\Epos;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Form for removing a voucher when tendering a transaction in EPOS.
 *
 * @deprecated Moved to EPOS module, use Message\Mothership\Epos\Form\Voucher\VoucherRemove instead
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class VoucherRemove extends AbstractType
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
	public function getName()
	{
		return 'epos_tender_voucher_remove';
	}
}