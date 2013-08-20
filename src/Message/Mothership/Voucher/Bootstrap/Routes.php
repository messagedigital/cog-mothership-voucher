<?php

namespace Message\Mothership\Voucher\Bootstrap;

use Message\Cog\Bootstrap\RoutesInterface;

class Routes implements RoutesInterface
{
	public function registerRoutes($router)
	{
		$router['ms.voucher']->add('ms.voucher.process', '/voucher/add', '::Controller:AddVoucher#voucherProcess');

		return $router;
	}
}
