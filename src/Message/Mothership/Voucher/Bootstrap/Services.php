<?php

namespace Message\Mothership\Voucher\Bootstrap;

use Message\Mothership\Voucher;

use Message\Cog\Bootstrap\ServicesInterface;

class Services implements ServicesInterface
{
	public function registerServices($container)
	{
		$container['order.payment.methods'] = $container->share($container->extend('order.payment.methods', function($methods) {
			$methods->add(new Voucher\PaymentMethod\Voucher);

			return $methods;
		}));
	}
}