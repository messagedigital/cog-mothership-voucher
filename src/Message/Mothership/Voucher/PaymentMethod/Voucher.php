<?php

namespace Message\Mothership\Voucher;

use Message\Mothership\Commerce\Order\Entity\Payment\MethodInterface;

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