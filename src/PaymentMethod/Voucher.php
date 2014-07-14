<?php

namespace Message\Mothership\Voucher\PaymentMethod;

use Message\Mothership\Commerce\Payment\MethodInterface;

/**
 * Payment method for a face-value voucher.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class Voucher implements MethodInterface
{
	/**
	 * {@inheritDoc}
	 */
	public function getName()
	{
		return 'voucher';
	}

	/**
	 * {@inheritDoc}
	 */
	public function getDisplayName()
	{
		return 'Face Value Voucher';
	}
}