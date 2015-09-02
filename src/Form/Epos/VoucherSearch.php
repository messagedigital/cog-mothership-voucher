<?php

namespace Message\Mothership\Voucher\Form\Epos;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Form for searching for a voucher when tendering a transaction in EPOS.
 *
 * @deprecated Moved to EPOS module, use Message\Mothership\Epos\Form\Voucher\VoucherSearch instead
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class VoucherSearch extends AbstractType
{
	/**
	 * {@inheritdoc}
	 */
	public function buildForm(FormBuilderInterface $builder, array $options)
	{
		$builder->add('id', 'text', [
			'constraints' => new Constraints\NotBlank,
			'label'       => 'Please enter or type a voucher\'s code below',
		]);
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return 'epos_tender_voucher_search';
	}
}