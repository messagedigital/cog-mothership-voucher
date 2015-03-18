<?php

namespace Message\Mothership\Voucher\Event;

use Message\Mothership\Voucher\Voucher;
use Message\Cog\Event\Event;

/**
 * Class VoucherEvent
 * @package Message\Mothership\Voucher\Event
 *
 * @author Thomas Marchant <thomas@mothership.ec>
 */
class VoucherEvent extends Event
{
	/**
	 * @var Voucher
	 */
	private $_voucher;

	/**
	 * @param Voucher $voucher
	 */
	public function __construct(Voucher $voucher)
	{
		$this->_voucher = $voucher;
	}

	/**
	 * @return Voucher
	 */
	public function getVoucher()
	{
		return $this->_voucher;
	}
}