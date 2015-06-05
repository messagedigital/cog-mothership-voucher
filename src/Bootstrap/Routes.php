<?php

namespace Message\Mothership\Voucher\Bootstrap;

use Message\Cog\Bootstrap\RoutesInterface;

class Routes implements RoutesInterface
{
	public function registerRoutes($router)
	{
		$router['ms.voucher']->add('ms.voucher.process', '/voucher/add', 'Message:Mothership:Voucher::Controller:AddVoucher#voucherProcess');

		$router['ms.cp.voucher']->setParent('ms.cp')->setPrefix('/voucher');

		$router['ms.cp.voucher']->add('ms.cp.voucher.index', '', 'Message:Mothership:Voucher::Controller:ControlPanel#index');
		$router['ms.cp.voucher']->add('ms.cp.voucher.create.action', '/create', 'Message:Mothership:Voucher::Controller:ControlPanel#createAction')
			->setMethod('POST');
		$router['ms.cp.voucher']->add('ms.cp.voucher.create', '/create', 'Message:Mothership:Voucher::Controller:ControlPanel#create');
		$router['ms.cp.voucher']->add('ms.cp.voucher.search', '/search', 'Message:Mothership:Voucher::Controller:ControlPanel#search')
			->setMethod('POST');

		$router['ms.cp.voucher']->add('ms.cp.voucher.invalidate', '/{id}/invalidate', 'Message:Mothership:Voucher::Controller:ControlPanel#invalidate')
			->setRequirement('id', '[A-Z0-9]+')
			->setMethod('DELETE');

		$router['ms.cp.voucher']->add('ms.cp.voucher.view', '/{id}', 'Message:Mothership:Voucher::Controller:ControlPanel#view');

	}
}
