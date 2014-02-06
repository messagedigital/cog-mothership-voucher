<?php

namespace Message\Mothership\Voucher;

use Message\Cog\ValueObject\Authorship;
use Message\Cog\ValueObject\DateTimeImmutable;


/**
 * Represents a single face-value voucher.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class Voucher
{
	public $authorship;

	public $id;
	public $currencyID;
	public $amount;

	public $startsAt;
	public $expiresAt;
	public $usedAt;
	public $purchasedAsItem;
	public $usage = array();

	public function __construct()
	{
		$this->authorship = new Authorship;

		$this->authorship
			->disableUpdate()
			->disableDelete();
	}

	/**
	 * Get the total amount of this voucher that has been used.
	 *
	 * @return float
	 */
	public function getAmountUsed()
	{
		$return = 0;

		foreach ($this->usage as $payment) {
			$return += $payment->amount;
		}

		return $return;
	}

	/**
	 * Get the remaining balance for this voucher (the original amount less the
	 * amount used to date).
	 *
	 * @return float
	 */
	public function getBalance()
	{
		return $this->amount - $this->getAmountUsed();
	}

	/**
	 * check whether the voucher is allowed to be applied to an order.
	 *
	 * @return boolean  Check whether the voucher hasn't expired and has a positive balance
	 */
	public function isUsable()
	{
		return $this->getBalance() > 0
		 	&& (is_null($this->startsAt)  || $this->startsAt->getTimestamp() < time())
		 	&& (is_null($this->expiresAt) || $this->expiresAt->getTimestamp() > time());
	}

	/**
	 * if voucher is not usuable get the reason.
	 *
	 * @return string
	 */
	public function getError()
	{
		switch ($this) {
		    case $this->getBalance() <= 0:
		        $error = "no balance";
		        return $error;
		    case $this->startsAt && $this->startsAt->getTimestamp() > time():
		        $error = "not started";
		     	return $error;
		    case $this->expiresAt && $this->expiresAt->getTimestamp() < time():
		     	$error = "expired";
		     	return $error;
		    default:
		     	$error = NULL;
		}
	}
}