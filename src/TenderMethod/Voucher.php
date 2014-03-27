<?php

namespace Message\Mothership\Voucher\TenderMethod;

use Message\Mothership\Epos\TenderMethod\MethodInterface;

/**
 * Voucher tender method.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class Voucher implements MethodInterface
{
	/**
	 * {@inheritdoc}
	 */
	public function getName()
	{
		return 'voucher';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getDisplayName()
	{
		return 'Gift Voucher';
	}

	/**
	 * {@inheritdoc}
	 */
	public function getPaymentMethod()
	{
		return 'voucher';
	}

	/**
	 * {@inheritdoc}
	 *
	 * Change is not allowed.
	 */
	public function isChangeAllowed()
	{
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getNumberPadReplacementRequestUrl()
	{
		return 'hi';
	}
}