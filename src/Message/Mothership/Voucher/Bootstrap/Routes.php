<?php

namespace Message\Mothership\Voucher\Bootstrap;

use Message\Cog\Bootstrap\RoutesInterface;

class Routes implements RoutesInterface
{
	public function registerRoutes($router)
	{
		$router['ms.voucher']->add('ms.voucher.process', '/voucher/add', '::Controller:AddVoucher#voucherProcess');

		$router['ms.cp.voucher']->setParent('ms.cp')->setPrefix('/voucher');

		$router['ms.cp.voucher']->add('ms.cp.voucher.index', '', '::Controller:ControlPanel#index');
		$router['ms.cp.voucher']->add('ms.cp.voucher.create.action', '/create', '::Controller:ControlPanel#createAction')
			->setMethod('POST');
		$router['ms.cp.voucher']->add('ms.cp.voucher.create', '/create', '::Controller:ControlPanel#create');
		$router['ms.cp.voucher']->add('ms.cp.voucher.search', '/search', '::Controller:ControlPanel#search')
			->setMethod('POST');

		$router['ms.cp.voucher']->add('ms.cp.voucher.view', '/{id}', '::Controller:ControlPanel#view');
	}
}
