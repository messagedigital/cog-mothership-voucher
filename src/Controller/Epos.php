<?php

namespace Message\Mothership\Voucher\Controller;

use Message\Cog\Controller\Controller;

/**
 * Controllers for voucher-specific functionality in EPOS.
 *
 * @author Joe Holdcroft <joe@message.co.uk>
 */
class Epos extends Controller
{
	public function tenderVoucher()
	{
		return $this->render('Message:Mothership:Voucher::epos:tender-voucher');
	}
}