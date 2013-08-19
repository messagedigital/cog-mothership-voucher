<?php

namespace Message\Mothership\Voucher\Bootstrap;

use Message\Mothership\Voucher;

use Message\Cog\Bootstrap\ServicesInterface;

class Services implements ServicesInterface
{
	public function registerServices($container)
	{
		$container['voucher.loader'] = function($c) {
			return new Voucher\Loader($c['db.query'], $c['order.item.loader'], $c['order.payment.loader']);
		};

		$container['voucher.create'] = function($c) {
			return new Voucher\Loader($c['db.query'], $c['order.item.loader'], $c['order.payment.loader']);
		};

		// Add voucher payment method
		$container['order.payment.methods'] = $container->share($container->extend('order.payment.methods', function($methods) {
			$methods->add(new Voucher\PaymentMethod\Voucher);

			return $methods;
		}));
	}
}