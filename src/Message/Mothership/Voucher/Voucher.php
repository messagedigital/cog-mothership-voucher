<?php

namespace Message\Mothership\Voucher;

/**
 * Represents a single face-value voucher.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class Voucher
{
	public $authorship;

	public $id;
	public $code;
	public $currencyID;
	public $amount;

	public $expiresAt;
	public $usedAt;
	public $purchasedAsItem;
	public $usage = array();

	public function getAmountUsed()
	{

	}

	public function getBalance()
	{

	}
}